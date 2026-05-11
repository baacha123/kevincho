<?php
/*
Plugin Name: KC Fix 6
Description: Kevin Cho homepage styling - CSS via output buffer injection bypasses 10Web
*/

// ========== OUTPUT BUFFER CSS INJECTION ==========
// Start OB at mu-plugin load time (before regular plugins like 10Web)
// LIFO order: our outer buffer callback runs AFTER 10Web's inner buffer
// So we see the final processed HTML and can inject CSS that won't be stripped
function kc6_get_css() {
    return
    // Header/logo
    '#masthead .header .logo img,.header .logo img,.header .logo a img{display:none!important;visibility:hidden!important;width:0!important;height:0!important;opacity:0!important}'
    .'.header .logo a noscript{display:none!important}'
    .'.header .logo a{display:block!important;background:url(https://kevincho.com/wp-content/uploads/2025/05/CEVIN-LOGO-WHITE.png) no-repeat center/contain!important;width:180px!important;height:45px!important;text-decoration:none!important;overflow:hidden!important;text-indent:-9999px!important}'
    .'.header .logo{position:absolute!important;left:50%!important;top:50%!important;transform:translate(-50%,-50%)!important;z-index:10!important}'
    .'#masthead .header,header#masthead .header,.elementor-4803 .header{background-color:#402417!important;position:relative!important}'
    .'.elementor-4803 .e-con-boxed,.elementor-4803 .elementor-element.e-con-boxed{max-width:100%!important;width:100%!important;padding:0!important}'
    .'.elementor-4803 .e-con-inner{max-width:100%!important;width:100%!important}'
    .'.elementor-4803{width:100%!important;max-width:100%!important}'
    // Page layout
    .'.page-id-5660 .container{max-width:100%!important;width:100%!important;padding:0!important;margin:0!important}'
    .'.page-id-5660 .entry-content{max-width:100%!important;width:100%!important;padding:0!important;margin:0!important}'
    .'.page-id-5660 article.post-5660{max-width:100%!important;width:100%!important;padding:0!important;margin:0!important}'
    .'.page-id-5660 .entry-header{display:none!important}'
    .'.page-id-5660 .post-edit-link,.page-id-5660 .entry-footer,.page-id-5660 a[href*="action=edit"]{display:none!important}'
    .'.page-id-5660 .hfeed.site{overflow-x:hidden}'
    // Global reset
    .'.kc-home *,.kc-home *::before,.kc-home *::after{margin:0;padding:0;box-sizing:border-box}'
    .'.kc-home{--brand:#402417;--gold:#c9a96e;font-family:"Cormorant Garamond",Georgia,serif;color:#402417;width:100%;max-width:100%;opacity:1!important;visibility:visible!important}'
    // Hero
    .'.kc-hero-zone{position:relative;height:100vh}'
    .'.kc-hero-pin{position:sticky;top:0;height:100vh;overflow:hidden;background:url(https://kevincho.com/wp-content/uploads/2026/02/kevin-cho-sewing-desktop.png) center center/cover no-repeat}'
    .'.kc-hero-overlay{position:absolute;bottom:0;left:0;right:0;text-align:center;z-index:5}'
    .'.kc-btn-hero{transition:all 0.3s}'
    .'.kc-btn-hero:hover{background:#c9a96e!important;color:#402417!important}'
    // Animations
    .'.kc-content-fade{opacity:1;transform:translateY(0);transition:opacity 0.8s,transform 0.8s}'
    .'.kc-content-fade.kc-anim{opacity:0;transform:translateY(40px)}'
    .'.kc-content-fade.kc-vis{opacity:1!important;transform:translateY(0)!important}'
    // Collection cards (4-column editorial)
    .'.kc-collections{padding:80px 20px 60px;background:#faf7f3;text-align:center}'
    .'.kc-coll-grid{display:grid!important;grid-template-columns:repeat(4,1fr)!important;gap:24px!important;max-width:1200px!important;margin:0 auto!important}'
    .'.kc-coll-card{text-decoration:none!important;display:block!important;transition:transform 0.35s}'
    .'.kc-coll-card:hover{transform:translateY(-6px)}'
    .'.kc-coll-img{height:380px;border-radius:12px;overflow:hidden;transition:box-shadow 0.35s}'
    .'.kc-coll-card:hover .kc-coll-img{box-shadow:0 12px 32px rgba(64,36,23,0.2)}'
    .'.kc-coll-card h3{font-size:clamp(20px,2.5vw,28px);font-weight:400;color:#402417;margin:20px 0 8px 0;font-family:"Cormorant Garamond",Georgia,serif;transition:color 0.3s}'
    .'.kc-coll-card:hover h3{color:#c9a96e}'
    .'.kc-coll-card p{font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#c9a96e;font-family:"Open Sans",sans-serif;font-weight:400;margin:0;line-height:1.6}'
    .'.kc-coll-btn{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);padding:10px 28px;font-size:10px;letter-spacing:3px;text-transform:uppercase;font-family:"Open Sans",sans-serif;border:1px solid #fff;color:#fff;background:rgba(0,0,0,0.3);z-index:3;transition:all 0.3s;white-space:nowrap}'
    .'.kc-coll-card:hover .kc-coll-btn{background:#fff;color:#402417;border-color:#fff}'
    // Custom wear banner
    .'.kc-custom-banner{position:relative;overflow:hidden}'
    .'.kc-custom-banner::before{content:"";position:absolute;inset:0;background:url(https://kevincho.com/wp-content/uploads/2026/02/kevin-cho-sewing-desktop.png) center center/cover no-repeat;opacity:0.15;z-index:0}'
    // Buttons
    .'.kc-btn-row{display:flex;gap:20px;justify-content:center;flex-wrap:wrap}'
    .'.kc-btn{display:inline-block;padding:16px 44px;font-size:12px;letter-spacing:3px;text-transform:uppercase;text-decoration:none;transition:all 0.35s;font-family:"Open Sans",sans-serif}'
    .'.kc-btn-gold{background:#c9a96e;color:#402417;border:2px solid #c9a96e}'
    .'.kc-btn-gold:hover{background:transparent;color:#c9a96e}'
    .'.kc-btn-outline{background:transparent;color:#fff;border:2px solid rgba(255,255,255,0.4)}'
    .'.kc-btn-outline:hover{border-color:#c9a96e;color:#c9a96e}'
    // Section styling
    .'.kc-section-tag{font-size:12px;letter-spacing:6px;text-transform:uppercase;color:#c9a96e;margin-bottom:16px;font-family:"Open Sans",sans-serif}'
    .'.kc-section-h2{font-size:clamp(30px,5vw,48px);font-weight:300;color:#402417;margin-bottom:60px;line-height:1.2}'
    // Features
    .'.kc-features{padding:80px 20px;background:#fff;text-align:center}'
    .'.kc-feat-grid{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))!important;gap:60px!important;max-width:960px!important;margin:0 auto!important}'
    .'.kc-feat-item{padding:20px}'
    .'.kc-feat-icon{width:48px;height:48px;margin:0 auto 20px;stroke:#c9a96e;fill:none;stroke-width:1.2}'
    .'.kc-feat-item h3{font-size:24px;font-weight:400;margin-bottom:12px}'
    .'.kc-feat-item p{font-size:15px;line-height:1.8;color:#6b5a4e;font-family:"Open Sans",sans-serif;font-weight:300}'
    // CTA
    .'.kc-cta{padding:100px 20px;background:#402417;text-align:center}'
    .'.kc-cta h2{font-size:clamp(28px,5vw,46px);color:#fff;font-weight:300;margin-bottom:16px}'
    .'.kc-cta p{color:rgba(255,255,255,0.65);font-size:16px;max-width:480px;margin:0 auto 44px;font-family:"Open Sans",sans-serif;font-weight:300;line-height:1.8}'
    // Body opacity
    .'body.page-id-5660{opacity:1!important}'
    // Responsive
    .'@media(max-width:1024px){'
    .'.kc-coll-grid{grid-template-columns:repeat(2,1fr)!important;gap:20px!important}'
    .'.kc-coll-img{height:320px!important}'
    .'}'
    .'@media(max-width:768px){'
    .'.kc-coll-grid{grid-template-columns:1fr!important;gap:24px!important;max-width:400px!important}'
    .'.kc-coll-img{height:260px!important;border-radius:10px!important}'
    .'.kc-collections{padding:60px 16px 40px!important}'
    .'.kc-custom-banner{height:350px!important}'
    .'.kc-features,.kc-cta{padding:60px 16px}'
    .'.header .logo{left:16px!important;transform:translateY(-50%)!important}'
    .'.header .logo a{width:140px!important;height:36px!important}'
    .'.kc-hero-pin{background-image:url(https://kevincho.com/wp-content/uploads/2026/02/kevin-cho-sewing-mobile-3.png)!important;background-size:cover!important;background-position:center center!important;-webkit-background-size:cover!important}'
    .'.kc-hero-zone{height:100vh!important}'
    .'.kc-hero-overlay h1{font-size:28px!important}'
    .'.kc-hero-overlay p{font-size:13px!important}'
    .'}';
}

function kc6_inject_css_buffer($html) {
    // Safety: only process actual HTML pages
    if (strlen($html) < 200) return $html;
    if (stripos($html, '<html') === false && stripos($html, '<!doctype') === false) return $html;

    $css = kc6_get_css();

    // Strategy 1: Inject INTO 10Web's existing critical CSS tag
    // This is the safest - our CSS becomes part of 10Web's own critical CSS
    if (preg_match('/(<style[^>]*class=["\'][^"\']*two_critical_css[^"\']*["\'][^>]*>)/i', $html, $m)) {
        $html = str_replace($m[1], $m[1] . $css, $html);
    } else if (stripos($html, '</head>') !== false) {
        // Strategy 2: Inject before </head> as a standalone style tag
        $html = str_ireplace('</head>', '<style id="kc-critical">' . $css . '</style></head>', $html);
    }

    // Hide body at HTML level to prevent ANY flash before CSS loads
    // Inline style takes effect on first parse frame - zero flash
    // Our CSS rule body.page-id-5660{opacity:1!important} overrides this
    // IMPORTANT: Check body class specifically, not entire HTML (our CSS contains 'page-id-5660' as selectors)
    if (preg_match('/<body[^>]*class=["\'][^"\']*page-id-5660/', $html)) {
        if (preg_match('/<body([^>]*?)(\s*style\s*=\s*["\'])/', $html)) {
            // Body already has style attr - prepend opacity:0
            $html = preg_replace('/(<body[^>]*?)(\s*style\s*=\s*["\'])/', '$1$2opacity:0;', $html, 1);
        } else {
            // Body has no style attr - add one
            $html = preg_replace('/(<body\b)/', '$1 style="opacity:0"', $html, 1);
        }
    }

    return $html;
}

// ========== FIX: Currency switcher breaking homepage ==========
// When ?currency=GBP (or similar) is added, WP treats it as a blog query
// instead of the static front page. This forces WP to load page 5660.
add_action('pre_get_posts', function($query) {
    if (!$query->is_main_query() || is_admin()) return;
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($path === '/' || $path === '') {
        if ($query->is_home() && !$query->is_page() && get_option('show_on_front') === 'page') {
            $front_id = (int) get_option('page_on_front');
            if ($front_id) {
                $query->set('page_id', $front_id);
                $query->set('post_type', 'page');
                $query->is_page = true;
                $query->is_singular = true;
                $query->is_home = false;
                $query->is_archive = false;
            }
        }
    }
});

// Start output buffer at mu-plugin load time (earliest possible)
// Mu-plugins load BEFORE regular plugins, so our OB is the outermost buffer
// LIFO: inner buffers (10Web) process first, then ours processes the final result
$_kc6_uri = $_SERVER['REQUEST_URI'] ?? '';
if (
    php_sapi_name() !== 'cli' &&
    !defined('DOING_CRON') &&
    strpos($_kc6_uri, '/wp-json/') === false &&
    strpos($_kc6_uri, '/wp-admin/') === false &&
    strpos($_kc6_uri, 'admin-ajax.php') === false &&
    strpos($_kc6_uri, '/wp-login.php') === false
) {
    ob_start('kc6_inject_css_buffer');
}

// ========== INLINE JS via wp_footer ==========
add_action('wp_footer', 'kc6_inline_js', 99);
function kc6_inline_js() {
?>
<script id="kc-inline-js">
!function(){
function kc(){
    if(document.getElementById('kc-anim-img'))return;
    var h=document.querySelector('#masthead .header')||document.querySelector('.header');
    if(!h)return;
    var wl='https://kevincho.com/wp-content/uploads/2025/05/CEVIN-LOGO-WHITE.png';
    h.querySelectorAll('.logo img').forEach(function(im){
        im.src=wl;
        if(im.dataset&&im.dataset.src)im.dataset.src=wl;
        im.removeAttribute('srcset');
        im.removeAttribute('data-srcset');
        im.style.display='block';
        im.style.visibility='visible';
        im.style.position='static';
        im.style.clip='auto';
        im.style.maxWidth='180px';
        im.style.height='auto';
    });
    h.querySelectorAll('.logo noscript').forEach(function(n){
        n.style.display='none';
    });
    var hLogo=h.querySelector('.logo');
    var hContact=h.querySelector('.contact-us');
    var vh=window.innerHeight,vw=window.innerWidth;
    var heroZone=document.querySelector('.kc-hero-zone');
    if(!heroZone)return;
    var overlay=document.createElement('img');
    overlay.id='kc-anim-img';
    overlay.src=wl;
    overlay.alt='Kevin Cho';
    overlay.style.cssText='position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(1);z-index:100000;pointer-events:none;opacity:0;will-change:transform;width:160px;height:auto;transform-origin:center center;';
    document.body.appendChild(overlay);
    var miniHdr=document.createElement('div');
    miniHdr.id='kc-mini-hdr';
    miniHdr.style.cssText='position:fixed;top:0;left:0;right:0;height:50px;background:#402417;z-index:99990;display:flex;align-items:center;justify-content:center;transform:translateY(-100%);transition:transform 0.4s cubic-bezier(0.4,0,0.2,1);';
    var miniImg=document.createElement('img');
    miniImg.src=wl;
    miniImg.style.cssText='height:24px;width:auto;';
    miniImg.alt='Kevin Cho';
    miniHdr.appendChild(miniImg);
    document.body.appendChild(miniHdr);
    var hdrH=h.offsetHeight||55;
    var startY=hdrH/2;
    var totalScroll=vh*1.2;
    var initW=160;
    var peakScale=(vw*1.1)/initW;
    var ticking=false;
    function ease(t){return t<0.5?2*t*t:-1+(4-2*t)*t}
    function lerp(a,b,t){return a+(b-a)*t}
    function upd(){
        ticking=false;
        var sY=window.pageYOffset||0;
        var p=Math.min(Math.max(sY/totalScroll,0),1);
        if(p<0.02){
            overlay.style.opacity='0';
            overlay.style.transform='translate(-50%,-50%) scale(1)';
            overlay.style.top='50%';
            if(hLogo)hLogo.style.opacity='';
            if(hContact)hContact.style.opacity='';
            if(h)h.style.cssText='';
            miniHdr.style.transform='translateY(-100%)';
        }else if(p<0.50){
            var t=ease((p-0.02)/0.48);
            var sc=lerp(1,peakScale,t);
            var yPos=lerp(startY,vh*0.45,t);
            overlay.style.top=yPos+'px';
            overlay.style.transform='translate(-50%,-50%) scale('+sc+')';
            var op=Math.min(t*3,1);
            if(t>0.4)op=lerp(1,0.15,(t-0.4)/0.6);
            overlay.style.opacity=op;
            if(hLogo)hLogo.style.opacity=Math.max(1-t*5,0);
            if(hContact)hContact.style.opacity=Math.max(1-t*4,0);
            if(h){var ba=Math.max(1-t*3,0);h.style.backgroundColor='rgba(64,36,23,'+ba+')';}
            miniHdr.style.transform='translateY(-100%)';
        }else if(p<0.70){
            overlay.style.top=(vh*0.45)+'px';
            overlay.style.transform='translate(-50%,-50%) scale('+peakScale+')';
            overlay.style.opacity='0.12';
            if(hLogo)hLogo.style.opacity='0';
            if(hContact)hContact.style.opacity='0';
            if(h)h.style.backgroundColor='rgba(64,36,23,0)';
            miniHdr.style.transform='translateY(-100%)';
        }else if(p<0.92){
            var t2=(p-0.70)/0.22;
            overlay.style.opacity=lerp(0.12,0,t2);
            if(hLogo)hLogo.style.opacity='0';
            if(hContact)hContact.style.opacity='0';
            if(h)h.style.backgroundColor='rgba(64,36,23,0)';
            miniHdr.style.transform='translateY(-100%)';
        }else{
            overlay.style.opacity='0';
            if(hLogo)hLogo.style.opacity='0';
            if(hContact)hContact.style.opacity='0';
            if(h)h.style.backgroundColor='rgba(64,36,23,0)';
            miniHdr.style.transform='translateY(0)';
        }
    }
    window.addEventListener('scroll',function(){
        if(!ticking){requestAnimationFrame(upd);ticking=true;}
    });
    window.addEventListener('resize',function(){
        vh=window.innerHeight;vw=window.innerWidth;
        peakScale=(vw*1.1)/initW;
        hdrH=h.offsetHeight||55;
        startY=hdrH/2;
        totalScroll=vh*1.2;
        upd();
    });
    upd();
    var fades=document.querySelectorAll('.kc-content-fade');
    if('IntersectionObserver' in window){
        fades.forEach(function(el){el.classList.add('kc-anim');});
        var obs=new IntersectionObserver(function(ents){
            ents.forEach(function(e){
                if(e.isIntersecting){e.target.classList.add('kc-vis');obs.unobserve(e.target);}
            });
        },{threshold:0.12});
        fades.forEach(function(el){obs.observe(el);});
    }
}
if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',function(){setTimeout(kc,200);});
}else{
    setTimeout(kc,200);
}
window.addEventListener('load',function(){setTimeout(kc,500);});
}();
</script>
<?php
}

// ========== PAGE UPDATE TRIGGER (file-based) ==========
add_action('init', 'kc6_apply_page_file', 5);
function kc6_apply_page_file() {
    $dir = wp_upload_dir();
    $f = $dir['basedir'] . '/kc-page-content.html';
    if (!file_exists($f)) return;
    $html = file_get_contents($f);
    if (strlen($html) < 10) return;
    global $wpdb;
    $now = current_time('mysql');
    $gmt = current_time('mysql', 1);
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->posts} SET post_content = %s, post_modified = %s, post_modified_gmt = %s WHERE ID = %d",
        $html, $now, $gmt, 5660
    ));
    delete_post_meta(5660, '_elementor_data');
    delete_post_meta(5660, '_elementor_edit_mode');
    delete_post_meta(5660, '_elementor_css');
    clean_post_cache(5660);
    wp_cache_flush();
    if (function_exists('sg_cachepress_purge_everything')) sg_cachepress_purge_everything();
    unlink($f);
    file_put_contents($dir['basedir'] . '/kc-update-log.txt', 'done time=' . $now . ' len=' . strlen($html));
}

// ========== REST API ENDPOINTS ==========
add_action('rest_api_init', function() {
    $ns = 'kc/v1';

    $auth = function($req) {
        return $req->get_header('X-KC-Key') === 'kc-temp-2026-xK9mP3vQ7wR';
    };

    register_rest_route($ns, '/get-page', [
        'methods' => 'GET',
        'callback' => function($req) {
            $id = intval($req->get_param('id') ?: 5660);
            $p = get_post($id);
            if (!$p) return new WP_Error('not_found', 'Page not found', ['status' => 404]);
            return [
                'id' => $p->ID,
                'title' => $p->post_title,
                'status' => $p->post_status,
                'modified' => $p->post_modified,
                'content_length' => strlen($p->post_content),
                'first_80' => substr($p->post_content, 0, 80),
                'has_elementor' => !!get_post_meta($id, '_elementor_data', true),
            ];
        },
        'permission_callback' => $auth,
    ]);

    register_rest_route($ns, '/update-page', [
        'methods' => 'POST',
        'callback' => function($req) {
            $id = intval($req->get_param('id') ?: 5660);
            $content = $req->get_param('content');
            if (!$content) return new WP_Error('no_content', 'No content', ['status' => 400]);
            global $wpdb;
            $now = current_time('mysql');
            $gmt = current_time('mysql', 1);
            $rows = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->posts} SET post_content = %s, post_modified = %s, post_modified_gmt = %s WHERE ID = %d",
                $content, $now, $gmt, $id
            ));
            delete_post_meta($id, '_elementor_data');
            delete_post_meta($id, '_elementor_edit_mode');
            delete_post_meta($id, '_elementor_css');
            clean_post_cache($id);
            wp_cache_flush();
            if (function_exists('sg_cachepress_purge_everything')) sg_cachepress_purge_everything();
            return ['success' => true, 'page_id' => $id, 'rows' => $rows];
        },
        'permission_callback' => $auth,
    ]);

    register_rest_route($ns, '/write-upload', [
        'methods' => 'POST',
        'callback' => function($req) {
            $fn = sanitize_file_name($req->get_param('filename'));
            $fc = $req->get_param('file_content');
            if (!$fn || !$fc) return new WP_Error('missing', 'Need filename + file_content', ['status' => 400]);
            $dir = wp_upload_dir();
            $path = $dir['basedir'] . '/' . $fn;
            $bytes = file_put_contents($path, $fc);
            return ['success' => true, 'path' => $path, 'bytes' => $bytes];
        },
        'permission_callback' => $auth,
    ]);

    register_rest_route($ns, '/purge', [
        'methods' => 'GET',
        'callback' => function() {
            wp_cache_flush();
            if (function_exists('sg_cachepress_purge_everything')) sg_cachepress_purge_everything();
            return ['purged' => true, 'time' => current_time('mysql')];
        },
        'permission_callback' => $auth,
    ]);

    register_rest_route($ns, '/manage-cat', [
        'methods' => 'POST',
        'callback' => function($req) {
            $action = $req->get_param('action') ?: 'create';
            $name = $req->get_param('name');
            $slug = $req->get_param('slug');
            $parent = intval($req->get_param('parent'));
            if ($action === 'create') {
                $args = ['slug' => $slug];
                if ($parent) $args['parent'] = $parent;
                $result = wp_insert_term($name, 'product_cat', $args);
                if (is_wp_error($result)) return ['error' => $result->get_error_message()];
                return ['success' => true, 'term_id' => $result['term_id']];
            }
            if ($action === 'move') {
                $term_id = intval($req->get_param('term_id'));
                $result = wp_update_term($term_id, 'product_cat', ['parent' => $parent]);
                if (is_wp_error($result)) return ['error' => $result->get_error_message()];
                return ['success' => true, 'term_id' => $result['term_id']];
            }
            if ($action === 'list') {
                $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
                $out = [];
                foreach ($terms as $t) {
                    $out[] = ['id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'parent' => $t->parent, 'count' => $t->count];
                }
                return $out;
            }
            return new WP_Error('bad_action', 'Unknown action', ['status' => 400]);
        },
        'permission_callback' => $auth,
    ]);

    register_rest_route($ns, '/create-page', [
        'methods' => 'POST',
        'callback' => function($req) {
            kses_remove_filters();
            $id = wp_insert_post([
                'post_title' => $req->get_param('title') ?: 'New Page',
                'post_content' => $req->get_param('content') ?: '',
                'post_name' => $req->get_param('slug') ?: '',
                'post_status' => $req->get_param('status') ?: 'publish',
                'post_type' => 'page',
                'post_author' => 1,
            ]);
            kses_init_filters();
            if (is_wp_error($id)) return ['error' => $id->get_error_message()];
            return ['success' => true, 'page_id' => $id];
        },
        'permission_callback' => $auth,
    ]);
});
