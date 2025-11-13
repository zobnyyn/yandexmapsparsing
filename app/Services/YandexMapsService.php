<?php

namespace App\Services;

use App\Models\YandexSetting;
use Illuminate\Support\Facades\Http;

class YandexMapsService
{
    private const BASE_URL = 'https://yandex.ru/maps/org/';
    private const TIMEOUT = 30;

    public function parseReviews(string $url): array
    {
        if ($this->isShortUrl($url)) {
            $url = $this->resolveShortUrl($url);
            \Log::info('Resolved short URL to: ' . $url);
        }

        $orgId = $this->extractOrgId($url);
        \Log::info('Extracted Org ID: ' . $orgId);

        $html = $this->fetchPage($orgId);
        \Log::info('Fetched page length: ' . strlen($html));

        $rating = $this->extractRating($html);
        $reviewCount = $this->extractReviewCount($html);
        $companyName = $this->extractCompanyName($html);
        $companyPhoto = $this->extractCompanyPhoto($html);
        $reviews = $this->extractReviewsFromApi($orgId);

        if (empty($reviews)) {
            \Log::info('API reviews empty, fallback to HTML parsing');
            $reviews = $this->extractReviews($html);
        }

        \Log::info('Parsed data', [
            'rating' => $rating,
            'review_count' => $reviewCount,
            'company_name' => $companyName,
            'company_photo' => $companyPhoto,
            'reviews_count' => count($reviews)
        ]);

        return [
            'rating' => $rating,
            'review_count' => $reviewCount,
            'company_name' => $companyName,
            'company_photo' => $companyPhoto,
            'reviews' => $reviews,
            'fetched_at' => now()->toIso8601String()
        ];
    }

    private function isShortUrl(string $url): bool
    {
        return preg_match('/yandex\.ru\/maps\/-\/[A-Za-z0-9]+/', $url) === 1;
    }

    private function resolveShortUrl(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ])->timeout(30)->get($url);

            $finalUrl = $response->effectiveUri();

            if ($finalUrl && (string)$finalUrl !== $url) {
                \Log::info('Short URL resolved via effectiveUri', ['url' => (string)$finalUrl]);
                return (string)$finalUrl;
            }

            $html = $response->body();

            if (preg_match('/content="0;\s*url=([^"]+)"/', $html, $match)) {
                \Log::info('Short URL resolved via meta refresh', ['url' => $match[1]]);
                return $match[1];
            }

            if (preg_match('/window\.location\.href\s*=\s*["\']([^"\']+)["\']/', $html, $match)) {
                \Log::info('Short URL resolved via JS redirect', ['url' => $match[1]]);
                return $match[1];
            }

            if (preg_match('/data-bem="([^"]*)"/', $html, $match)) {
                $bem = json_decode(html_entity_decode($match[1]), true);
                if (isset($bem['serp-item']['data']['properties']['id'])) {
                    $orgId = $bem['serp-item']['data']['properties']['id'];
                    \Log::info('Org ID found in BEM data', ['id' => $orgId]);
                    return 'https://yandex.ru/maps/org/' . $orgId;
                }
            }

            if (preg_match('/"oid":"(\d+)"/', $html, $match)) {
                $orgId = $match[1];
                \Log::info('Org ID found in JSON', ['id' => $orgId]);
                return 'https://yandex.ru/maps/org/' . $orgId;
            }

            \Log::warning('Could not resolve short URL, using original', ['url' => $url]);
            return $url;
        } catch (\Exception $e) {
            \Log::error('Failed to resolve short URL', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            return $url;
        }
    }

    private function extractOrgId(string $url): string
    {
        if (preg_match('/\/org\/[^\/]*\/(\d+)/', $url, $matches)) {
            \Log::info('ID extracted via org path', ['id' => $matches[1]]);
            return $matches[1];
        }

        if (preg_match('/\/org\/(\d+)/', $url, $matches)) {
            \Log::info('ID extracted via simple org', ['id' => $matches[1]]);
            return $matches[1];
        }

        if (preg_match('/[?&]oid=(\d+)/', $url, $matches)) {
            \Log::info('ID extracted via oid param', ['id' => $matches[1]]);
            return $matches[1];
        }

        if (preg_match('/poi%5Buri%5D=ymapsbm1[^&]*%3Foid%3D(\d+)/', $url, $matches)) {
            \Log::info('ID extracted via encoded poi URI', ['id' => $matches[1]]);
            return $matches[1];
        }

        $decodedUrl = urldecode($url);
        if (preg_match('/oid=(\d+)/', $decodedUrl, $matches)) {
            \Log::info('ID extracted via decoded oid', ['id' => $matches[1]]);
            return $matches[1];
        }

        if (preg_match('/\/(\d{10,})(?:\/|$|\?)/', $url, $matches)) {
            \Log::info('ID extracted via long number', ['id' => $matches[1]]);
            return $matches[1];
        }

        \Log::info('Attempting to fetch short URL', ['url' => $url]);

        try {
            $html = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9',
            ])->timeout(30)->get($url)->body();

            \Log::info('Fetched HTML length', ['length' => strlen($html)]);

            if (preg_match('/"businessOid":"?(\d+)"?/', $html, $matches)) {
                \Log::info('ID extracted via businessOid', ['id' => $matches[1]]);
                return $matches[1];
            }

            if (preg_match('/"oid":"?(\d+)"?/', $html, $matches)) {
                \Log::info('ID extracted via oid', ['id' => $matches[1]]);
                return $matches[1];
            }

            if (preg_match('/oid["\']?\s*:\s*["\']?(\d+)/', $html, $matches)) {
                \Log::info('ID extracted via oid flexible', ['id' => $matches[1]]);
                return $matches[1];
            }

            if (preg_match('/"id":"?(\d{10,})"?/', $html, $matches)) {
                \Log::info('ID extracted via id', ['id' => $matches[1]]);
                return $matches[1];
            }

            if (preg_match('/\/org\/(\d+)/', $html, $matches)) {
                \Log::info('ID extracted from HTML org path', ['id' => $matches[1]]);
                return $matches[1];
            }

            \Log::error('Could not find ID in HTML', ['html_sample' => substr($html, 0, 500)]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch short URL', ['error' => $e->getMessage()]);
        }

        throw new \InvalidArgumentException('Не удалось извлечь ID организации из URL. Попробуйте использовать другую ссылку из Яндекс Карт.');
    }

    private function fetchPage(string $orgId): string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer' => 'https://yandex.ru/maps/',
        ])->timeout(self::TIMEOUT)->get(self::BASE_URL . $orgId . '/reviews/');

        if ($response->failed()) {
            throw new \Exception('Failed to fetch Yandex Maps page');
        }

        $html = $response->body();

        file_put_contents(storage_path('logs/yandex_page_' . $orgId . '.html'), $html);
        \Log::info('Saved HTML to: storage/logs/yandex_page_' . $orgId . '.html');

        return $html;
    }

    private function extractRating(string $html): float
    {
        if (preg_match('/<meta\s+itemProp="ratingValue"\s+content="(\d+\.?\d*)"/', $html, $match)) {
            \Log::info('Rating extracted from meta tag', ['rating' => $match[1]]);
            return (float) $match[1];
        }

        if (preg_match('/<span\s+class="business-summary-rating-badge-view__rating-text">(\d+)<\/span>\s*<span[^>]*>([,\.])?\s*<\/span>\s*<span[^>]*>(\d+)<\/span>/s', $html, $match)) {
            $rating = $match[1] . '.' . $match[3];
            \Log::info('Rating extracted from badge view', ['rating' => $rating]);
            return (float) $rating;
        }

        if (preg_match('/<span\s+class="[^"]*business-rating-badge-view__rating-text[^"]*"[^>]*>(\d+),(\d+)<\/span>/', $html, $match)) {
            $rating = $match[1] . '.' . $match[2];
            \Log::info('Rating extracted from rating badge (comma)', ['rating' => $rating]);
            return (float) $rating;
        }

        if (preg_match('/<span\s+class="[^"]*business-rating-badge-view__rating-text[^"]*"[^>]*>(\d+)\.(\d+)<\/span>/', $html, $match)) {
            $rating = $match[1] . '.' . $match[2];
            \Log::info('Rating extracted from rating badge (dot)', ['rating' => $rating]);
            return (float) $rating;
        }

        if (preg_match('/<script[^>]*class="state-view"[^>]*>(.*?)<\/script>/s', $html, $scriptMatch)) {
            $json = json_decode($scriptMatch[1], true);

            if (isset($json['stack'][0]['results']['items'][0]['ratingData']['ratingValue'])) {
                $rating = $json['stack'][0]['results']['items'][0]['ratingData']['ratingValue'];
                \Log::info('Rating extracted from state-view stack', ['rating' => $rating]);
                return (float) $rating;
            }
        }

        if (preg_match('/"ratingValue":\s*(\d+\.?\d*)/', $html, $match)) {
            \Log::info('Rating extracted from JSON ratingValue', ['rating' => $match[1]]);
            return (float) $match[1];
        }

        \Log::warning('Could not extract rating');
        return 0.0;
    }

    private function extractReviewCount(string $html): int
    {
        if (preg_match('/<meta\s+itemProp="ratingCount"\s+content="(\d+)"/', $html, $match)) {
            \Log::info('Rating count extracted from meta tag', ['count' => $match[1]]);
            return (int) $match[1];
        }

        if (preg_match('/<span\s+class="business-rating-amount-view[^"]*"[^>]*>(\d+)\s+оцен/', $html, $match)) {
            \Log::info('Rating count extracted from rating amount view', ['count' => $match[1]]);
            return (int) $match[1];
        }

        if (preg_match('/<div\s+class="[^"]*business-header-rating-view__text[^"]*"[^>]*>(\d+)\s+оцен/', $html, $match)) {
            \Log::info('Rating count extracted from header rating view', ['count' => $match[1]]);
            return (int) $match[1];
        }

        if (preg_match('/<script[^>]*class="state-view"[^>]*>(.*?)<\/script>/s', $html, $scriptMatch)) {
            $json = json_decode($scriptMatch[1], true);

            if (isset($json['stack'][0]['results']['items'][0]['ratingData']['ratingCount'])) {
                $count = $json['stack'][0]['results']['items'][0]['ratingData']['ratingCount'];
                \Log::info('Rating count extracted from state-view stack', ['count' => $count]);
                return (int) $count;
            }
        }

        if (preg_match('/"ratingCount":\s*(\d+)/', $html, $match)) {
            \Log::info('Rating count extracted from JSON ratingCount', ['count' => $match[1]]);
            return (int) $match[1];
        }

        if (preg_match('/itemprop="reviewCount"[^>]*content="(\d+)"/', $html, $match)) {
            \Log::info('Rating count extracted from reviewCount meta', ['count' => $match[1]]);
            return (int) $match[1];
        }

        \Log::warning('Could not extract review count');
        return 0;
    }

    private function extractReviewsFromApi(string $orgId): array
    {
        try {
            $csrfToken = $this->getCsrfToken();

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => 'https://yandex.ru/maps/org/' . $orgId,
                'X-Csrf-Token' => $csrfToken,
            ])->timeout(30)->get('https://yandex.ru/maps/api/business/fetch_reviews', [
                'oid' => $orgId,
                'page' => 1,
                'pageSize' => 10,
                'businessReviews' => 1,
                'ranking' => 'by_time',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data']['reviews'])) {
                    $reviews = [];

                    foreach ($data['data']['reviews'] as $review) {
                        $author = $review['author']['name'] ?? 'Аноним';
                        $rating = $review['rating'] ?? 5;
                        $text = $review['text'] ?? '';

                        $date = date('Y-m-d');
                        if (isset($review['updatedTime'])) {
                            $date = date('Y-m-d', strtotime($review['updatedTime']));
                        } elseif (isset($review['createdTime'])) {
                            $date = date('Y-m-d', strtotime($review['createdTime']));
                        }

                        $reviews[] = [
                            'author' => $author,
                            'rating' => (int) $rating,
                            'text' => trim($text) ?: 'Без текста',
                            'date' => $date
                        ];

                        if (count($reviews) >= 10) {
                            break;
                        }
                    }

                    \Log::info('Extracted reviews from API', ['count' => count($reviews)]);
                    return $reviews;
                }
            }

            \Log::warning('API request failed or no reviews', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200)
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch reviews from API', ['error' => $e->getMessage()]);
        }

        return [];
    }

    private function getCsrfToken(): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->timeout(30)->get('https://yandex.ru/maps/');

            if (preg_match('/"csrfToken":"([^"]+)"/', $response->body(), $match)) {
                return $match[1];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to get CSRF token', ['error' => $e->getMessage()]);
        }

        return '';
    }

    private function extractReviews(string $html): array
    {
        $reviews = [];

        if (preg_match('/<script[^>]*class="state-view"[^>]*>(.*?)<\/script>/s', $html, $scriptMatch)) {
            try {
                $jsonData = json_decode($scriptMatch[1], true);

                \Log::info('Found state-view script, parsing JSON');

                if (isset($jsonData['views'])) {
                    foreach ($jsonData['views'] as $viewKey => $view) {
                        \Log::info('Processing view', ['key' => $viewKey]);

                        if (isset($view['businessReviews']['items'])) {
                            $reviewItems = $view['businessReviews']['items'];
                            \Log::info('Found businessReviews items', ['count' => count($reviewItems)]);

                            foreach ($reviewItems as $item) {
                                $author = $item['author']['name'] ?? 'Аноним';
                                $rating = $item['rating'] ?? 5;
                                $text = $item['text'] ?? '';

                                $date = null;
                                if (isset($item['updatedTime'])) {
                                    $timestamp = strtotime($item['updatedTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                } elseif (isset($item['createdTime'])) {
                                    $timestamp = strtotime($item['createdTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                }

                                if ($date === null) {
                                    $date = date('Y-m-d H:i:s');
                                }

                                $reviews[] = [
                                    'author' => $author,
                                    'rating' => (int) $rating,
                                    'text' => trim($text) ?: 'Без текста',
                                    'date' => $date
                                ];

                                if (count($reviews) >= 10) {
                                    break 2;
                                }
                            }
                        }

                        if (isset($view['reviews']['items']) && count($reviews) < 10) {
                            $reviewItems = $view['reviews']['items'];
                            \Log::info('Found reviews items', ['count' => count($reviewItems)]);

                            foreach ($reviewItems as $item) {
                                $author = $item['author']['name'] ?? 'Аноним';
                                $rating = $item['rating'] ?? 5;
                                $text = $item['text'] ?? '';

                                $date = null;
                                if (isset($item['updatedTime'])) {
                                    $timestamp = strtotime($item['updatedTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                } elseif (isset($item['createdTime'])) {
                                    $timestamp = strtotime($item['createdTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                }

                                if ($date === null) {
                                    $date = date('Y-m-d H:i:s');
                                }

                                $reviews[] = [
                                    'author' => $author,
                                    'rating' => (int) $rating,
                                    'text' => trim($text) ?: 'Без текста',
                                    'date' => $date
                                ];

                                if (count($reviews) >= 10) {
                                    break 2;
                                }
                            }
                        }
                    }
                }

                if (count($reviews) === 0 && isset($jsonData['stack'])) {
                    foreach ($jsonData['stack'] as $stackItem) {
                        if (isset($stackItem['businessReviews']['items'])) {
                            $reviewItems = $stackItem['businessReviews']['items'];
                            \Log::info('Found businessReviews in stack', ['count' => count($reviewItems)]);

                            foreach ($reviewItems as $item) {
                                $author = $item['author']['name'] ?? 'Аноним';
                                $rating = $item['rating'] ?? 5;
                                $text = $item['text'] ?? '';

                                $date = null;
                                if (isset($item['updatedTime'])) {
                                    $timestamp = strtotime($item['updatedTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                } elseif (isset($item['createdTime'])) {
                                    $timestamp = strtotime($item['createdTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                }

                                if ($date === null) {
                                    $date = date('Y-m-d H:i:s');
                                }

                                $reviews[] = [
                                    'author' => $author,
                                    'rating' => (int) $rating,
                                    'text' => trim($text) ?: 'Без текста',
                                    'date' => $date
                                ];

                                if (count($reviews) >= 10) {
                                    break 2;
                                }
                            }
                        }

                        if (isset($stackItem['reviewResults']['reviews']) && count($reviews) < 10) {
                            $reviewItems = $stackItem['reviewResults']['reviews'];
                            \Log::info('Found reviewResults in stack', ['count' => count($reviewItems)]);

                            foreach ($reviewItems as $item) {
                                $author = $item['author']['name'] ?? 'Аноним';
                                $rating = $item['rating'] ?? 5;
                                $text = $item['text'] ?? '';

                                $date = null;
                                if (isset($item['updatedTime'])) {
                                    $timestamp = strtotime($item['updatedTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                } elseif (isset($item['createdTime'])) {
                                    $timestamp = strtotime($item['createdTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                }

                                if ($date === null) {
                                    $date = date('Y-m-d H:i:s');
                                }

                                $reviews[] = [
                                    'author' => $author,
                                    'rating' => (int) $rating,
                                    'text' => trim($text) ?: 'Без текста',
                                    'date' => $date
                                ];

                                if (count($reviews) >= 10) {
                                    break 2;
                                }
                            }
                        }

                        if (isset($stackItem['reviews']['items']) && count($reviews) < 10) {
                            $reviewItems = $stackItem['reviews']['items'];
                            \Log::info('Found reviews in stack', ['count' => count($reviewItems)]);

                            foreach ($reviewItems as $item) {
                                $author = $item['author']['name'] ?? 'Аноним';
                                $rating = $item['rating'] ?? 5;
                                $text = $item['text'] ?? '';

                                $date = null;
                                if (isset($item['updatedTime'])) {
                                    $timestamp = strtotime($item['updatedTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                } elseif (isset($item['createdTime'])) {
                                    $timestamp = strtotime($item['createdTime']);
                                    if ($timestamp !== false) {
                                        $date = date('Y-m-d H:i:s', $timestamp);
                                    }
                                }

                                if ($date === null) {
                                    $date = date('Y-m-d H:i:s');
                                }

                                $reviews[] = [
                                    'author' => $author,
                                    'rating' => (int) $rating,
                                    'text' => trim($text) ?: 'Без текста',
                                    'date' => $date
                                ];

                                if (count($reviews) >= 10) {
                                    break 2;
                                }
                            }
                        }
                    }
                }

                if (count($reviews) > 0) {
                    \Log::info('Extracted reviews from state-view', ['count' => count($reviews)]);
                    return $reviews;
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse state-view JSON', ['error' => $e->getMessage()]);
            }
        }

        if (preg_match_all('/<div\s+class="business-review-view"[^>]*itemProp="review"[^>]*>(.*?)(?:<div\s+class="business-review-view__actions"|<div\s+class="business-reviews-card-view__review")/s', $html, $matches)) {
            \Log::info('Found review blocks via HTML', ['count' => count($matches[1])]);

            foreach ($matches[1] as $reviewHtml) {
                $author = 'Аноним';
                if (preg_match('/<span\s+itemProp="name"[^>]*>(.*?)<\/span>/', $reviewHtml, $authorMatch)) {
                    $author = trim(strip_tags($authorMatch[1]));
                }

                $rating = 5;
                if (preg_match('/<meta\s+itemProp="ratingValue"\s+content="(\d+\.?\d*)"/', $reviewHtml, $ratingMatch)) {
                    $rating = (int) round((float) $ratingMatch[1]);
                }

                $text = '';
                if (preg_match('/<div[^>]*class="[^"]*spoiler-view__text[^"]*"[^>]*>(.*?)<\/div>/s', $reviewHtml, $textMatch)) {
                    $text = strip_tags($textMatch[1]);
                    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = str_replace(['&quot;', '&#039;'], ['"', "'"], $text);
                    $text = preg_replace('/\s+/', ' ', $text);
                    $text = trim($text);
                }

                $date = date('Y-m-d H:i:s');
                if (preg_match('/<meta\s+itemProp="datePublished"\s+content="([^"]+)"/', $reviewHtml, $dateMatch)) {
                    $timestamp = strtotime($dateMatch[1]);
                    if ($timestamp !== false) {
                        $date = date('Y-m-d H:i:s', $timestamp);
                    }
                }

                $reviews[] = [
                    'author' => $author,
                    'rating' => $rating,
                    'text' => $text ?: 'Без текста',
                    'date' => $date
                ];

                if (count($reviews) >= 10) {
                    break;
                }
            }

            if (count($reviews) > 0) {
                \Log::info('Extracted reviews from HTML', ['count' => count($reviews)]);
                return $reviews;
            }
        }

        \Log::warning('Could not extract reviews');
        return $reviews;
    }

    private function extractCompanyName(string $html): string
    {
        if (preg_match('/<h1[^>]*class="[^"]*orgpage-header-view__header[^"]*"[^>]*>(.*?)<\/h1>/s', $html, $match)) {
            $name = strip_tags($match[1]);
            $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $name = trim($name);
            \Log::info('Company name extracted from h1', ['name' => $name]);
            return $name;
        }

        if (preg_match('/<title>(.*?)\s*—\s*Яндекс\s*Карты<\/title>/', $html, $match)) {
            $name = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $name = trim($name);
            \Log::info('Company name extracted from title', ['name' => $name]);
            return $name;
        }

        if (preg_match('/"name"\s*:\s*"([^"]+)"/', $html, $match)) {
            $name = json_decode('"' . $match[1] . '"');
            \Log::info('Company name extracted from JSON', ['name' => $name]);
            return $name;
        }

        if (preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/', $html, $match)) {
            $name = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $name = preg_replace('/\s*—\s*Яндекс\s*Карты.*$/', '', $name);
            $name = trim($name);
            \Log::info('Company name extracted from og:title', ['name' => $name]);
            return $name;
        }

        \Log::warning('Could not extract company name');
        return 'Неизвестная компания';
    }

    private function extractCompanyPhoto(string $html): ?string
    {
        if (preg_match('/<img[^>]*class="[^"]*business-photos-carousel-view__image[^"]*"[^>]*src="([^"]+)"/', $html, $match)) {
            $url = $match[1];
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            }
            \Log::info('Company photo extracted from carousel', ['url' => $url]);
            return $url;
        }

        if (preg_match('/<div[^>]*class="[^"]*business-card-photo-view__image[^"]*"[^>]*style="[^"]*background-image:\s*url\(([^)]+)\)/', $html, $match)) {
            $url = trim($match[1], '\'"');
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            }
            \Log::info('Company photo extracted from card photo', ['url' => $url]);
            return $url;
        }

        if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/', $html, $match)) {
            $url = $match[1];
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            }
            \Log::info('Company photo extracted from og:image', ['url' => $url]);
            return $url;
        }

        if (preg_match('/"image"\s*:\s*"([^"]+)"/', $html, $match)) {
            $url = json_decode('"' . $match[1] . '"');
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            }
            \Log::info('Company photo extracted from JSON', ['url' => $url]);
            return $url;
        }

        if (preg_match('/<script[^>]*class="state-view"[^>]*>(.*?)<\/script>/s', $html, $scriptMatch)) {
            try {
                $jsonData = json_decode($scriptMatch[1], true);

                if (isset($jsonData['views'])) {
                    foreach ($jsonData['views'] as $view) {
                        if (isset($view['photos']['items'][0]['urlTemplate'])) {
                            $template = $view['photos']['items'][0]['urlTemplate'];
                            $url = str_replace('%{size}', '400x300', $template);
                            if (strpos($url, '//') === 0) {
                                $url = 'https:' . $url;
                            }
                            \Log::info('Company photo extracted from state-view photos', ['url' => $url]);
                            return $url;
                        }
                    }
                }

                if (isset($jsonData['stack'])) {
                    foreach ($jsonData['stack'] as $stackItem) {
                        if (isset($stackItem['photos']['items'][0]['urlTemplate'])) {
                            $template = $stackItem['photos']['items'][0]['urlTemplate'];
                            $url = str_replace('%{size}', '400x300', $template);
                            if (strpos($url, '//') === 0) {
                                $url = 'https:' . $url;
                            }
                            \Log::info('Company photo extracted from stack photos', ['url' => $url]);
                            return $url;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse company photo from JSON', ['error' => $e->getMessage()]);
            }
        }

        \Log::warning('Could not extract company photo');
        return null;
    }

    public function saveData(int $userId, array $data): YandexSetting
    {
        $setting = YandexSetting::where('user_id', $userId)->firstOrFail();

        $setting->update([
            'cached_data' => $data,
            'last_sync' => now()
        ]);

        return $setting;
    }

    public function getCachedData(int $userId): ?array
    {
        $setting = YandexSetting::where('user_id', $userId)->first();

        if (!$setting) {
            return null;
        }

        return $setting->cached_data;
    }
}

