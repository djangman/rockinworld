#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import requests
import time
import random
import json
import csv
import re
import argparse
from datetime import datetime, timedelta
from urllib.parse import urlencode

# ---------------------------------------------
# Argument Parsing
# ---------------------------------------------
def parse_args():
    parser = argparse.ArgumentParser(description="Reddit scraper with adaptive rate limiting")

    parser.add_argument("subreddits")
    parser.add_argument("query")

    parser.add_argument("--limit", type=int, default=50)
    parser.add_argument("--match", choices=["any", "all"], default="any")
    parser.add_argument("--exclude", nargs="*", default=[])
    parser.add_argument("--min-hits", type=int, default=1)

    parser.add_argument("--days", type=int)
    parser.add_argument("--start-date", type=lambda s: datetime.strptime(s, "%Y-%m-%d"))
    parser.add_argument("--end-date", type=lambda s: datetime.strptime(s, "%Y-%m-%d"))

    parser.add_argument("--sort", choices=["new", "relevance", "hot"], default="new")

    parser.add_argument(
        "--delay",
        type=float,
        default=0.6,
        help="Initial base delay in seconds"
    )

    parser.add_argument(
        "--max-delay",
        type=float,
        default=5.0,
        help="Maximum adaptive delay cap"
    )

    return parser.parse_args()


# ---------------------------------------------
# Query Parsing / Matching
# ---------------------------------------------
def parse_query(raw):
    phrases = re.findall(r'"([^"]+)"', raw)
    cleaned = re.sub(r'"[^"]+"', '', raw)
    terms = cleaned.replace(",", " ").split()
    return {
        "phrases": [p.lower() for p in phrases],
        "terms": [t.lower() for t in terms if t.strip()]
    }


def matches_query(content, query, match_mode, min_hits, exclude_terms):
    for term in exclude_terms:
        if term.lower() in content:
            return False

    for phrase in query["phrases"]:
        if phrase not in content:
            return False

    hits = sum(1 for term in query["terms"] if term in content)

    if match_mode == "all":
        return hits >= min_hits and hits == len(query["terms"])

    return hits >= min_hits


# ---------------------------------------------
# Adaptive Delay Controller
# ---------------------------------------------
class AdaptiveDelay:
    def __init__(self, base_delay, max_delay):
        self.base_delay = base_delay
        self.current_delay = base_delay
        self.max_delay = max_delay
        self.last_penalty = 0

    def penalize(self):
        self.current_delay = min(self.current_delay * 1.5, self.max_delay)
        self.last_penalty = time.time()
        print(f"[RATE] Increasing delay → {self.current_delay:.2f}s")

    def reward(self):
        if time.time() - self.last_penalty > 30:
            self.current_delay = max(self.base_delay, self.current_delay * 0.95)

    def sleep(self):
        time.sleep(self.current_delay)


# ---------------------------------------------
# Networking Helpers
# ---------------------------------------------
def retry_with_backoff(func, on_429=None, max_retries=6, base_delay=1.0):
    for attempt in range(max_retries):
        try:
            return func()
        except Exception as e:
            if "429" in str(e) and on_429:
                on_429()

            if attempt == max_retries - 1:
                raise

            delay = base_delay * (2 ** attempt)
            delay += random.uniform(0, delay * 0.2)
            time.sleep(delay)


def reddit_get(url, headers=None, adaptive_delay=None):
    def call():
        r = requests.get(url, headers=headers, timeout=15)
        if r.status_code == 429:
            raise Exception("429 Too Many Requests")
        r.raise_for_status()
        return r.json()

    result = retry_with_backoff(
        call,
        on_429=adaptive_delay.penalize if adaptive_delay else None
    )

    if adaptive_delay:
        adaptive_delay.reward()

    return result


# ---------------------------------------------
# Reddit Scraper
# ---------------------------------------------
class RedditScraper:
    BASE_URL = "https://www.reddit.com"
    USER_AGENT = "Mozilla/5.0 (compatible; redpy/1.1)"

    def __init__(self, args):
        self.subreddits = [s.strip() for s in args.subreddits.split(",")]
        self.query = args.query
        self.limit = args.limit
        self.match_mode = args.match
        self.exclude_terms = args.exclude
        self.min_hits = args.min_hits
        self.sort = args.sort

        self.headers = {"User-Agent": self.USER_AGENT}

        self.delay_ctl = AdaptiveDelay(args.delay, args.max_delay)

        now = datetime.utcnow()
        if args.start_date or args.end_date:
            self.start_date = args.start_date
            self.end_date = args.end_date
        else:
            days = args.days if args.days else 7
            self.start_date = now - timedelta(days=days)
            self.end_date = now

        self.parsed_query = parse_query(self.query)

    def _build_search_url(self, subreddit):
        q = urlencode({
            "q": self.query,
            "limit": self.limit,
            "sort": self.sort
        })
        return f"{self.BASE_URL}/r/{subreddit}/search.json?{q}&restrict_sr=on"

    def _filter_by_date(self, posts):
        filtered = []
        for post in posts:
            created = post.get("data", {}).get("created_utc", 0)
            if not created:
                continue
            dt = datetime.utcfromtimestamp(created)
            if self.start_date and dt < self.start_date:
                continue
            if self.end_date and dt > self.end_date:
                continue
            filtered.append(post)
        return filtered

    def fetch_comments(self, post_id):
        url = f"{self.BASE_URL}/comments/{post_id}.json"
        data = reddit_get(url, headers=self.headers, adaptive_delay=self.delay_ctl)

        if not isinstance(data, list) or len(data) < 2:
            return []

        comments = []
        for c in data[1].get("data", {}).get("children", []):
            if c.get("kind") != "t1":
                continue
            body = c.get("data", {}).get("body", "")
            if body and body != "[deleted]":
                comments.append(body)

        return comments

    def scrape(self):
        results = []

        for sub in self.subreddits:
            print(f"\n[INFO] Scraping r/{sub}")
            url = self._build_search_url(sub)
            data = reddit_get(url, headers=self.headers, adaptive_delay=self.delay_ctl)
            posts = self._filter_by_date(data.get("data", {}).get("children", []))

            kept = 0
            for post in posts:
                p = post.get("data", {})
                title = p.get("title", "")
                body = p.get("selftext", "")
                content = f"{title} {body}".lower()

                if not matches_query(
                    content,
                    self.parsed_query,
                    self.match_mode,
                    self.min_hits,
                    self.exclude_terms
                ):
                    continue

                kept += 1
                print(f"  - [{kept}] {title[:70]}")

                comments = self.fetch_comments(p.get("id"))
                combined = f"Title: {title}\n\nBody: {body}\n\nComments:\n" + "\n".join(comments)

                results.append({
                    "subreddit": sub,
                    "post_id": p.get("id"),
                    "title": title,
                    "body": body,
                    "url": self.BASE_URL + p.get("permalink", ""),
                    "created_utc": p.get("created_utc"),
                    "score": p.get("score"),
                    "num_comments": p.get("num_comments"),
                    "combined_text": combined
                })

                self.delay_ctl.sleep()

        return results


# ---------------------------------------------
# Output
# ---------------------------------------------
def save_results(results):
    ts = datetime.utcnow().strftime("%Y%m%d_%H%M%S")

    with open(f"reddit_scrape_{ts}.json", "w", encoding="utf-8") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)

    with open(f"reddit_scrape_{ts}.csv", "w", newline="", encoding="utf-8") as f:
        fields = ["subreddit", "post_id", "title", "body", "url",
                  "created_utc", "score", "num_comments", "combined_text"]
        writer = csv.DictWriter(f, fieldnames=fields)
        writer.writeheader()
        for r in results:
            writer.writerow(r)

    print(f"[INFO] Saved output → reddit_scrape_{ts}.json / .csv")


# ---------------------------------------------
# Main
# ---------------------------------------------
if __name__ == "__main__":
    args = parse_args()
    scraper = RedditScraper(args)
    results = scraper.scrape()
    save_results(results)
