# Nginx 502 Bad Gateway / Upstream Sent Too Big Header

## The Issue
When deploying modern Laravel applications using Vue, Inertia, and Vite plugins natively, the `AddLinkHeadersForPreloadedAssets` middleware injected via `bootstrap/app.php` parses and generates `<link rel="modulepreload">` for all internally requested frontend Javascript chunks. 

These module preload hints are injected directly as HTTP **Link headers** by PHP-FPM before the response hits Nginx. Because deep component trees heavily segment your frontend payload, dozens of headers are issued, exploding the total HTTP header block boundary past **4 KB - 8 KB**. 

Nginx's default FastCGI buffer sizes strictly crash with `502 Bad Gateway` (and print `upstream sent too big header while reading response header from upstream` to the log) when these accumulated Link headers exceed Nginx memory buffers.

## The Solution

To safely retain Laravel's automated HTTP/2 Push / Server Link header Preloading features, you must expand Nginx's FastCGI parsing boundaries. 

Add the following buffer limits specifically inside your Laravel domain's `location ~ \.php$` block:

```nginx
location ~ \.php$ {
    try_files $uri /index.php =404;
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    fastcgi_param DOCUMENT_ROOT $realpath_root;
    include fastcgi_params;

    # INCREASE BUFFER SIZES TO PREVENT VITE 502 HEADERS:
    fastcgi_buffer_size 128k;
    fastcgi_buffers 8 128k;
    fastcgi_busy_buffers_size 256k;
}
```

This prevents the Nginx proxy layer from collapsing under Laravel 11/12's heavy Vite HTTP response headers, ensuring high-traffic dashboard routes load resiliently.
