/**
 * RealSatisfied RSS Fetcher - Cloudflare Worker
 *
 * This worker fetches RSS feeds from RealSatisfied, caches them in KV,
 * and provides a fast JSON API for WordPress to consume.
 */

// KV Namespace binding - configure in Cloudflare dashboard
// Bind as: TESTIMONIALS_CACHE

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    // CORS headers for WordPress
    const corsHeaders = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
      'Content-Type': 'application/json',
    };

    // Handle CORS preflight
    if (request.method === 'OPTIONS') {
      return new Response(null, { headers: corsHeaders });
    }

    // Routes
    if (url.pathname === '/api/testimonials') {
      return await handleGetTestimonials(request, env, corsHeaders);
    }

    if (url.pathname === '/api/refresh') {
      return await handleRefresh(request, env, corsHeaders);
    }

    if (url.pathname === '/api/status') {
      return await handleStatus(env, corsHeaders);
    }

    return new Response('Not Found', { status: 404 });
  },

  // Scheduled worker - runs daily
  async scheduled(event, env, ctx) {
    await refreshAllCompanies(env);
  }
};

/**
 * Get testimonials from cache
 */
async function handleGetTestimonials(request, env, corsHeaders) {
  const url = new URL(request.url);
  const companyId = url.searchParams.get('company_id');

  if (!companyId) {
    return new Response(
      JSON.stringify({ error: 'Missing company_id parameter' }),
      { status: 400, headers: corsHeaders }
    );
  }

  // Try to get from KV cache
  const cacheKey = `testimonials:${companyId}`;
  const cached = await env.TESTIMONIALS_CACHE.get(cacheKey, 'json');

  if (cached) {
    return new Response(
      JSON.stringify({
        source: 'cache',
        cached_at: cached.cached_at,
        data: cached
      }),
      { headers: corsHeaders }
    );
  }

  // If not in cache, fetch and store
  const data = await fetchAndCacheCompanyData(companyId, env);

  if (data.error) {
    return new Response(
      JSON.stringify(data),
      { status: 500, headers: corsHeaders }
    );
  }

  return new Response(
    JSON.stringify({
      source: 'fresh',
      cached_at: new Date().toISOString(),
      data: data
    }),
    { headers: corsHeaders }
  );
}

/**
 * Manually refresh a company's data
 */
async function handleRefresh(request, env, corsHeaders) {
  const url = new URL(request.url);
  const companyId = url.searchParams.get('company_id');
  const apiKey = request.headers.get('X-API-Key');

  // Simple API key check (store in environment variable)
  if (apiKey !== env.API_KEY) {
    return new Response(
      JSON.stringify({ error: 'Unauthorized' }),
      { status: 401, headers: corsHeaders }
    );
  }

  if (!companyId) {
    return new Response(
      JSON.stringify({ error: 'Missing company_id parameter' }),
      { status: 400, headers: corsHeaders }
    );
  }

  const data = await fetchAndCacheCompanyData(companyId, env);

  return new Response(
    JSON.stringify({
      success: !data.error,
      message: data.error || 'Data refreshed successfully',
      data: data.error ? null : data
    }),
    { headers: corsHeaders }
  );
}

/**
 * Get cache status
 */
async function handleStatus(env, corsHeaders) {
  const keys = await env.TESTIMONIALS_CACHE.list();
  const companies = [];

  for (const key of keys.keys) {
    if (key.name.startsWith('testimonials:')) {
      const companyId = key.name.replace('testimonials:', '');
      const data = await env.TESTIMONIALS_CACHE.get(key.name, 'json');
      companies.push({
        company_id: companyId,
        cached_at: data?.cached_at,
        testimonial_count: data?.testimonials?.length || 0
      });
    }
  }

  return new Response(
    JSON.stringify({
      cached_companies: companies.length,
      companies: companies
    }),
    { headers: corsHeaders }
  );
}

/**
 * Fetch RSS data and cache it
 */
async function fetchAndCacheCompanyData(companyId, env) {
  const feedUrl = `https://rss.realsatisfied.com/rss/company/${companyId}`;

  try {
    // Fetch RSS with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

    const response = await fetch(feedUrl, {
      signal: controller.signal,
      headers: {
        'User-Agent': 'Cloudflare Worker RSS Fetcher'
      }
    });

    clearTimeout(timeoutId);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const xml = await response.text();
    const testimonials = parseRSSToTestimonials(xml);

    // Cache for 7 days
    const cacheData = {
      company_id: companyId,
      cached_at: new Date().toISOString(),
      testimonials: testimonials,
      total_count: testimonials.length
    };

    const cacheKey = `testimonials:${companyId}`;
    await env.TESTIMONIALS_CACHE.put(
      cacheKey,
      JSON.stringify(cacheData),
      { expirationTtl: 604800 } // 7 days in seconds
    );

    return cacheData;

  } catch (error) {
    console.error('Error fetching RSS:', error);
    return { error: error.message };
  }
}

/**
 * Parse RSS XML to testimonials array
 */
function parseRSSToTestimonials(xmlString) {
  const testimonials = [];

  // Simple regex parsing (Cloudflare Workers don't have DOMParser)
  // Extract items from RSS
  const itemRegex = /<item>([\s\S]*?)<\/item>/g;
  const matches = xmlString.matchAll(itemRegex);

  for (const match of matches) {
    const itemXml = match[1];

    const testimonial = {
      text: extractValue(itemXml, 'description'),
      customer_name: extractTitle(itemXml),
      customer_location: extractLocation(itemXml),
      customer_type: extractValue(itemXml, 'customer_type', 'realsatisfied'),
      agent_name: extractValue(itemXml, 'display_name', 'realsatisfied'),
      agent_avatar: extractValue(itemXml, 'avatar', 'realsatisfied'),
      office_name: extractValue(itemXml, 'office', 'realsatisfied'),
      link: extractValue(itemXml, 'link'),
      pub_date: extractValue(itemXml, 'pubDate'),
      rating: 5 // Default high rating
    };

    testimonials.push(testimonial);
  }

  return testimonials;
}

/**
 * Extract value from XML
 */
function extractValue(xml, tag, namespace = null) {
  let pattern;
  if (namespace) {
    pattern = new RegExp(`<${namespace}:${tag}><!\\[CDATA\\[([\\s\\S]*?)\\]\\]><\/${namespace}:${tag}>|<${namespace}:${tag}>([^<]*)<\/${namespace}:${tag}>`, 'i');
  } else {
    pattern = new RegExp(`<${tag}><!\\[CDATA\\[([\\s\\S]*?)\\]\\]><\/${tag}>|<${tag}>([^<]*)<\/${tag}>`, 'i');
  }

  const match = xml.match(pattern);
  return match ? (match[1] || match[2] || '').trim() : '';
}

/**
 * Extract customer name from title
 */
function extractTitle(xml) {
  const title = extractValue(xml, 'title');
  const parts = title.split(',');
  return parts[0]?.trim() || '';
}

/**
 * Extract location from title
 */
function extractLocation(xml) {
  const title = extractValue(xml, 'title');
  const parts = title.split(',');
  if (parts.length > 1) {
    return parts.slice(1).join(',').trim();
  }
  return '';
}

/**
 * Refresh all cached companies (called by cron)
 */
async function refreshAllCompanies(env) {
  // List of company IDs to refresh
  const companyIds = [
    '1d5090ddb597aa7bba',
    // Add more company IDs here
  ];

  for (const companyId of companyIds) {
    await fetchAndCacheCompanyData(companyId, env);
    // Wait 2 seconds between fetches to be nice to RSS server
    await new Promise(resolve => setTimeout(resolve, 2000));
  }

  console.log(`Refreshed ${companyIds.length} company feeds`);
}