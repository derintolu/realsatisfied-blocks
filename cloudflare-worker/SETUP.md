# Cloudflare Worker Setup for RealSatisfied Testimonials

This moves RSS fetching off your WordPress server to Cloudflare's edge network.

## Benefits
- ✅ No server load - RSS fetching happens on Cloudflare
- ✅ Global caching - Testimonials cached at edge locations
- ✅ Fast API - WordPress gets JSON in milliseconds
- ✅ Auto-refresh - Daily cron updates testimonials
- ✅ Cost effective - Free tier handles 100k requests/day

## Setup Steps

### 1. Create Cloudflare Account
- Sign up at https://cloudflare.com
- Note your Account ID from the dashboard

### 2. Install Wrangler CLI
```bash
npm install -g wrangler
wrangler login
```

### 3. Create KV Namespace
```bash
wrangler kv:namespace create "TESTIMONIALS_CACHE"
```
Save the namespace ID from the output.

### 4. Configure wrangler.toml
Edit `wrangler.toml`:
- Replace `YOUR_ACCOUNT_ID` with your Cloudflare account ID
- Replace `YOUR_KV_NAMESPACE_ID` with the namespace ID from step 3
- Generate a secure API key and replace `YOUR_SECURE_API_KEY`

### 5. Deploy Worker
```bash
cd cloudflare-worker
wrangler deploy
```

This will give you a URL like:
`https://realsatisfied-testimonials.YOUR-SUBDOMAIN.workers.dev`

### 6. Configure WordPress

Add to `wp-config.php`:
```php
define( 'RSOB_CLOUDFLARE_API_URL', 'https://realsatisfied-testimonials.YOUR-SUBDOMAIN.workers.dev' );
define( 'RSOB_USE_CLOUDFLARE', true );
```

### 7. Update Plugin to Use Cloudflare

In `blocks/testimonial-marquee/testimonial-marquee.php`, change the `get_company_testimonials` method:

```php
private function get_company_testimonials( $attributes ) {
    if ( defined( 'RSOB_USE_CLOUDFLARE' ) && RSOB_USE_CLOUDFLARE ) {
        // Use Cloudflare Worker API
        if ( ! class_exists( 'Cloudflare_Testimonials_Client' ) ) {
            require_once RSOB_PLUGIN_PATH . 'includes/class-cloudflare-testimonials-client.php';
        }

        $client = Cloudflare_Testimonials_Client::get_instance();
        $testimonials = $client->fetch_testimonials(
            $attributes['companyId'],
            array( 'limit' => $attributes['maxTestimonials'] * 2 )
        );

        if ( is_wp_error( $testimonials ) ) {
            error_log( 'Cloudflare API error: ' . $testimonials->get_error_message() );
            return array();
        }

        return $testimonials;
    }

    // Fall back to existing RSS method
    // ... existing code ...
}
```

## API Endpoints

### Get Testimonials
```
GET /api/testimonials?company_id=YOUR_COMPANY_ID
```

Returns cached testimonials (fetches if not cached).

### Refresh Testimonials (requires API key)
```
GET /api/refresh?company_id=YOUR_COMPANY_ID
Headers: X-API-Key: YOUR_API_KEY
```

Forces refresh of testimonials cache.

### Status
```
GET /api/status
```

Shows cached companies and last update times.

## Cost

Cloudflare Workers Free Tier:
- 100,000 requests/day
- 10ms CPU time per request
- Unlimited KV storage reads

This is more than enough for testimonials that update weekly.

## Monitoring

View metrics in Cloudflare dashboard:
- Workers > your-worker > Metrics
- Shows requests, errors, CPU time
- Set up alerts for errors

## Troubleshooting

### Test the API
```bash
curl https://your-worker.workers.dev/api/testimonials?company_id=1d5090ddb597aa7bba
```

### Check logs
```bash
wrangler tail
```

### Clear cache
Use the refresh endpoint with your API key to force update.

## Security

- API key protects refresh endpoint
- CORS configured for your domain
- Rate limiting built into Cloudflare
- DDoS protection included

## Next Steps

1. Add more company IDs to the cron refresh list
2. Set up custom domain (optional)
3. Add monitoring/alerting
4. Optimize caching strategy