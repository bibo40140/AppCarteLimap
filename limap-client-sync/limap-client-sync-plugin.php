<?php
/**
 * Plugin Name: LIMAP Client Sync Endpoint
 * Description: Receives signed client sync payloads from AppCarte and upserts/deletes CPT client posts.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function (): void {
  $supplierType = limap_sync_get_supplier_post_type();
  if (!is_singular('client') && !is_singular($supplierType)) {
        return;
    }
    limap_sync_output_styles();
});

function limap_sync_output_styles(): void
{
    ?>
    <style id="limap-client-sync-styles">
      .limap-client-sync {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #333;
        line-height: 1.6;
      }
      .limap-client-sync * {
        box-sizing: border-box;
      }
      .limap-client-sync h1 {
        font-size: 2.5em;
        font-weight: 700;
        margin: 0.5em 0 0.3em;
        color: #1a1a1a;
      }
      .limap-client-sync h2 {
        font-size: 1.8em;
        font-weight: 600;
        margin: 1.2em 0 0.6em;
        color: #2a2a2a;
      }
      .limap-client-sync p {
        margin: 0.8em 0;
        font-size: 1em;
      }
      .limap-client-sync a {
        color: #0066cc;
        text-decoration: none;
        font-weight: 500;
      }
      .limap-client-sync a:hover {
        text-decoration: underline;
      }
      .limap-client-sync strong {
        color: #1a1a1a;
        font-weight: 600;
      }

      .client-side-wrap {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3em;
        max-width: 1200px;
      }
      .left-col {
        display: flex;
        flex-direction: column;
      }
      .client-logo-wrap {
        margin-bottom: 1.5em;
      }
      .client-logo-main {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: block;
      }
      .left-col > p {
        font-size: 1.05em;
        line-height: 1.7;
        color: #555;
      }

      .project-info {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 1.5em;
      }
      .project-info p {
        margin: 1em 0;
        display: flex;
        align-items: flex-start;
        gap: 0.8em;
      }
      .project-info strong {
        display: block;
        min-width: 120px;
        flex-shrink: 0;
      }

      .limap-gallery {
        margin-top: 2em;
        margin-bottom: 2em;
      }
      .limap-gallery h2 {
        font-size: 1.4em;
        margin-bottom: 1em;
      }
      .limap-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5em;
      }
      .limap-gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      .limap-gallery-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
      }
      .limap-gallery-item img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        display: block;
      }

      .social-links {
        display: flex;
        gap: 1em;
        margin-top: 0.5em;
        flex-wrap: wrap;
      }
      .social-links a {
        display: inline-flex;
        align-items: center;
        padding: 0.5em 1em;
        background: #0066cc;
        color: white;
        border-radius: 4px;
        font-weight: 600;
        transition: background 0.3s ease;
      }
      .social-links a:hover {
        background: #0052a3;
        text-decoration: none;
      }

      .hours-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5em;
      }
      .hours-table td {
        padding: 0.5em 0;
        border-bottom: 1px solid #eee;
      }
      .hours-table td:first-child {
        font-weight: 600;
        color: #1a1a1a;
        width: 30%;
        padding-right: 1em;
      }

      @media (max-width: 768px) {
        .client-side-wrap {
          grid-template-columns: 1fr;
          gap: 2em;
        }
        .limap-client-sync h1 {
          font-size: 2em;
        }
        .limap-gallery-grid {
          grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        .limap-gallery-item img {
          height: 150px;
        }
      }
    </style>
    <?php
}

add_action('rest_api_init', function (): void {
    register_rest_route('limap-sync/v1', '/clients', [
        'methods' => 'POST',
        'callback' => 'limap_sync_handle_client_event',
        'permission_callback' => '__return_true',
    ]);

  register_rest_route('limap-sync/v1', '/suppliers', [
    'methods' => 'POST',
    'callback' => 'limap_sync_handle_supplier_event',
    'permission_callback' => '__return_true',
  ]);
});

function limap_sync_verify_signed_payload(WP_REST_Request $request)
{
  $secret = trim((string)get_option('limap_sync_secret', ''));
  if ($secret === '') {
    return new WP_Error('missing_secret', 'Missing limap sync secret.');
  }

  $timestamp = (string)$request->get_header('x-limap-timestamp');
  $signature = (string)$request->get_header('x-limap-signature');
  $rawBody = $request->get_body();

  if ($timestamp === '' || $signature === '' || $rawBody === '') {
    return new WP_Error('missing_headers', 'Missing required signature headers.');
  }

  if (!ctype_digit($timestamp)) {
    return new WP_Error('invalid_timestamp', 'Invalid timestamp.');
  }

  $ts = (int)$timestamp;
  if (abs(time() - $ts) > 300) {
    return new WP_Error('timestamp_expired', 'Expired timestamp.');
  }

  $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
  if (!hash_equals($expected, $signature)) {
    return new WP_Error('bad_signature', 'Invalid signature.');
  }

  $payload = json_decode($rawBody, true);
  if (!is_array($payload)) {
    return new WP_Error('bad_json', 'Invalid JSON payload.');
  }

  return $payload;
}

function limap_sync_handle_client_event(WP_REST_Request $request): WP_REST_Response
{
  $payload = limap_sync_verify_signed_payload($request);
  if (is_wp_error($payload)) {
    return new WP_REST_Response(['ok' => false, 'error' => $payload->get_error_code()], 401);
  }

    $event = (string)($payload['event'] ?? '');
    $client = is_array($payload['client'] ?? null) ? $payload['client'] : [];
    $idSource = isset($client['id_source']) ? (int)$client['id_source'] : 0;

    if ($idSource <= 0) {
        return new WP_REST_Response(['ok' => false, 'error' => 'missing_id_source'], 422);
    }

    if ($event === 'client_delete' || !empty($client['public_visible']) === false) {
        $deleted = limap_sync_delete_client_post($idSource);
        return new WP_REST_Response(['ok' => true, 'action' => 'delete', 'deleted' => $deleted], 200);
    }

    $postId = limap_sync_upsert_client_post($client);
    if (is_wp_error($postId)) {
        return new WP_REST_Response([
            'ok' => false,
            'error' => 'upsert_failed',
            'message' => $postId->get_error_message(),
        ], 500);
    }

    return new WP_REST_Response(['ok' => true, 'action' => 'upsert', 'post_id' => (int)$postId], 200);
}

  function limap_sync_handle_supplier_event(WP_REST_Request $request): WP_REST_Response
  {
    $payload = limap_sync_verify_signed_payload($request);
    if (is_wp_error($payload)) {
      return new WP_REST_Response(['ok' => false, 'error' => $payload->get_error_code()], 401);
    }

    $event = (string)($payload['event'] ?? '');
    $supplier = is_array($payload['supplier'] ?? null) ? $payload['supplier'] : $payload;
    $idSource = isset($supplier['id_source']) ? (int)$supplier['id_source'] : (int)($payload['id_source'] ?? 0);

    if ($idSource <= 0) {
      return new WP_REST_Response(['ok' => false, 'error' => 'missing_id_source'], 422);
    }

    $publicVisible = !empty($supplier['public_visible']) || !empty($payload['public_visible']);
    if ($event === 'supplier_delete' || !$publicVisible) {
      $deleted = limap_sync_delete_supplier_post($idSource, $supplier);
      return new WP_REST_Response(['ok' => true, 'action' => 'delete', 'deleted' => $deleted], 200);
    }

    $postId = limap_sync_upsert_supplier_post($supplier);
    if (is_wp_error($postId)) {
      return new WP_REST_Response([
        'ok' => false,
        'error' => 'upsert_failed',
        'message' => $postId->get_error_message(),
      ], 500);
    }

    return new WP_REST_Response(['ok' => true, 'action' => 'upsert', 'post_id' => (int)$postId], 200);
  }

function limap_sync_find_client_post_id(int $idSource): int
{
    $query = new WP_Query([
        'post_type' => 'client',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => 'limap_id_source',
            'value' => (string)$idSource,
        ]],
    ]);

    if (!empty($query->posts)) {
        return (int)$query->posts[0];
    }

    return 0;
}

function limap_sync_delete_client_post(int $idSource): bool
{
    $postId = limap_sync_find_client_post_id($idSource);
    if ($postId <= 0) {
        return false;
    }

    wp_delete_post($postId, true);
    return true;
}

function limap_sync_upsert_client_post(array $client)
{
    $idSource = (int)($client['id_source'] ?? 0);
    $name = sanitize_text_field((string)($client['name'] ?? ''));
    $slug = sanitize_title((string)($client['slug'] ?? ''));

    if ($idSource <= 0 || $name === '') {
        return new WP_Error('invalid_client_payload', 'Missing required client payload fields.');
    }

    $postId = limap_sync_find_client_post_id($idSource);
    $content = limap_sync_render_client_content($client);
    $excerpt = wp_strip_all_tags((string)($client['description_short'] ?? ''));

    $postData = [
        'post_type' => 'client',
        'post_status' => 'publish',
        'post_title' => $name,
        'post_name' => $slug !== '' ? $slug : null,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
    ];

    if ($postId > 0) {
        $postData['ID'] = $postId;
        $result = wp_update_post($postData, true);
    } else {
        $result = wp_insert_post($postData, true);
        if (!is_wp_error($result)) {
            $postId = (int)$result;
        }
    }

    if (is_wp_error($result)) {
        return $result;
    }

    update_post_meta($postId, 'limap_id_source', (string)$idSource);
    update_post_meta($postId, 'limap_payload', wp_json_encode($client));
    limap_sync_update_featured_image($postId, (string)($client['logo_url'] ?? ''), (string)($client['photo_cover_url'] ?? ''));

    return $postId;
}

function limap_sync_render_client_content(array $client): string
{
    $f = static function (string $key) use ($client): string {
        return trim((string)($client[$key] ?? ''));
    };

    $title = esc_html($f('name'));
    $desc = wp_kses_post($f('description_long')); // Allow HTML rendering
    $type = esc_html($f('client_type'));
    $city = esc_html($f('city'));
    $address = esc_html(trim($f('address') . ' ' . $f('postal_code') . ' ' . $f('city') . ' ' . $f('country')));
    $phone = esc_html($f('phone'));
    $email = esc_html($f('email'));
    $website = esc_url($f('website'));
    $logo = esc_url($f('logo_url'));
    $facebook = esc_url($f('facebook_url') !== '' ? $f('facebook_url') : $f('facebook'));
    $instagram = esc_url($f('instagram_url') !== '' ? $f('instagram_url') : $f('instagram'));
    $linkedin = esc_url($f('linkedin_url') !== '' ? $f('linkedin_url') : $f('linkedin'));
    $galleryJson = $f('gallery_images');
    $gallery = [];
    if ($galleryJson !== '') {
        $decoded = json_decode($galleryJson, true);
        if (is_array($decoded)) {
            $gallery = $decoded;
        }
    }

    $schedule = [
        'Lundi' => $f('lundi'),
        'Mardi' => $f('mardi'),
        'Mercredi' => $f('mercredi'),
        'Jeudi' => $f('jeudi'),
        'Vendredi' => $f('vendredi'),
        'Samedi' => $f('samedi'),
        'Dimanche' => $f('dimanche'),
    ];

    ob_start();
    ?>
    <div class="client-side-wrap limap-client-sync">
      <div class="left-col">
        <div class="client-logo-wrap">
          <?php if ($logo !== ''): ?>
            <img class="client-logo-main" src="<?php echo $logo; ?>" alt="<?php echo $title; ?>" loading="lazy" />
          <?php endif; ?>
        </div>
        <h1><?php echo $title; ?></h1>
        <?php if ($desc !== ''): ?>
          <div><?php echo $desc; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($gallery)): ?>
          <div class="limap-gallery">
            <h2>Galerie</h2>
            <div class="limap-gallery-grid">
              <?php foreach ($gallery as $image): ?>
                <?php $imgUrl = esc_url($image['url'] ?? ''); ?>
                <?php if ($imgUrl !== ''): ?>
                  <div class="limap-gallery-item">
                    <img src="<?php echo $imgUrl; ?>" alt="<?php echo $title; ?>" loading="lazy" />
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="right-col">
        <div class="project-info">
          <p><strong>Type:</strong> <?php echo $type; ?></p>
          <p><strong>Ville:</strong> <?php echo $city; ?></p>
          <p><strong>Adresse:</strong> <?php echo $address; ?></p>
          <p><strong>Téléphone:</strong> <?php echo $phone; ?></p>
          <?php if ($email !== ''): ?>
            <p><strong>Email:</strong> <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></p>
          <?php endif; ?>
          <?php if ($website !== ''): ?>
            <p><strong>Site web:</strong> <a href="<?php echo $website; ?>" target="_blank" rel="noopener"><?php echo $website; ?></a></p>
          <?php endif; ?>

          <?php if ($facebook !== '' || $instagram !== '' || $linkedin !== ''): ?>
            <div>
              <strong style="display: block; margin-bottom: 0.8em;">Réseaux sociaux</strong>
              <div class="social-links">
                <?php if ($facebook !== ''): ?><a href="<?php echo $facebook; ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                <?php if ($instagram !== ''): ?><a href="<?php echo $instagram; ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                <?php if ($linkedin !== ''): ?><a href="<?php echo $linkedin; ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <div>
            <strong style="display: block; margin: 1.5em 0 0.8em;">Horaires</strong>
            <table class="hours-table">
              <?php foreach ($schedule as $day => $hours): ?>
                <tr>
                  <td><?php echo esc_html($day); ?></td>
                  <td><?php echo esc_html($hours !== '' ? $hours : 'Fermé'); ?></td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php
    return (string)ob_get_clean();
}

  function limap_sync_get_supplier_post_type(): string
  {
    $configured = sanitize_key((string)get_option('limap_sync_supplier_post_type', 'producteur'));
    if ($configured !== '' && post_type_exists($configured)) {
      return $configured;
    }

    foreach (['producteur', 'producteurs', 'producer', 'producers', 'supplier', 'suppliers', 'fournisseur', 'fournisseurs'] as $candidate) {
      if (post_type_exists($candidate)) {
        return $candidate;
      }
    }

    return $configured !== '' ? $configured : 'producteur';
  }

  function limap_sync_get_supplier_post_types_for_lookup(): array
  {
    $types = [];

    $primary = limap_sync_get_supplier_post_type();
    if ($primary !== '') {
      $types[] = $primary;
    }

    foreach (['producteur', 'producteurs', 'producer', 'producers', 'supplier', 'suppliers', 'fournisseur', 'fournisseurs'] as $candidate) {
      if (post_type_exists($candidate)) {
        $types[] = $candidate;
      }
    }

    return array_values(array_unique(array_filter($types)));
  }

  function limap_sync_find_supplier_post_id(int $idSource): int
  {
    $postTypes = limap_sync_get_supplier_post_types_for_lookup();
    foreach (['limap_supplier_id_source', 'limap_id_source', 'id_source'] as $metaKey) {
      $query = new WP_Query([
        'post_type' => $postTypes,
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [[
          'key' => $metaKey,
          'value' => (string)$idSource,
        ]],
      ]);

      if (!empty($query->posts)) {
        return (int)$query->posts[0];
      }
    }

    return 0;
  }

  function limap_sync_find_supplier_post_id_by_identity(array $supplier): int
  {
    $postTypes = limap_sync_get_supplier_post_types_for_lookup();
    $slug = sanitize_title((string)($supplier['slug'] ?? ''));
    if ($slug !== '') {
      $query = new WP_Query([
        'post_type' => $postTypes,
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'name' => $slug,
      ]);
      if (!empty($query->posts)) {
        return (int)$query->posts[0];
      }
    }

    $name = sanitize_text_field((string)($supplier['name'] ?? ''));
    if ($name !== '') {
      $needle = limap_sync_normalize_lookup_text($name);
      $allIds = get_posts([
        'post_type' => $postTypes,
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
      ]);

      foreach ($allIds as $postId) {
        $title = (string)get_the_title((int)$postId);
        if ($title !== '' && limap_sync_normalize_lookup_text($title) === $needle) {
          return (int)$postId;
        }
      }

      $query = new WP_Query([
        'post_type' => $postTypes,
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'title' => $name,
      ]);
      if (!empty($query->posts)) {
        return (int)$query->posts[0];
      }
    }

    return 0;
  }

  function limap_sync_normalize_lookup_text(string $value): string
  {
    $value = trim($value);
    if ($value === '') {
      return '';
    }

    if (function_exists('remove_accents')) {
      $value = remove_accents($value);
    }

    $value = mb_strtolower($value, 'UTF-8');
    $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
  }

  function limap_sync_delete_supplier_post(int $idSource, array $supplier = []): bool
  {
    $postId = limap_sync_find_supplier_post_id($idSource);
    if ($postId <= 0 && !empty($supplier)) {
      $postId = limap_sync_find_supplier_post_id_by_identity($supplier);
    }
    if ($postId <= 0) {
      return false;
    }

    wp_delete_post($postId, true);
    return true;
  }

  function limap_sync_upsert_supplier_post(array $supplier)
  {
    $idSource = (int)($supplier['id_source'] ?? 0);
    $name = sanitize_text_field((string)($supplier['name'] ?? ''));
    $slug = sanitize_title((string)($supplier['slug'] ?? ''));

    if ($idSource <= 0 || $name === '') {
      return new WP_Error('invalid_supplier_payload', 'Missing required supplier payload fields.');
    }

    $postId = limap_sync_find_supplier_post_id($idSource);
    if ($postId <= 0) {
      // Legacy imports may not have limap_supplier_id_source meta yet.
      $postId = limap_sync_find_supplier_post_id_by_identity($supplier);
    }
    $content = limap_sync_render_supplier_content($supplier);
    $excerpt = wp_strip_all_tags((string)($supplier['description_short'] ?? ''));

    $postData = [
      'post_type' => limap_sync_get_supplier_post_type(),
      'post_status' => 'publish',
      'post_title' => $name,
      'post_name' => $slug !== '' ? $slug : null,
      'post_content' => $content,
      'post_excerpt' => $excerpt,
    ];

    if ($postId > 0) {
      $postData['ID'] = $postId;
      $result = wp_update_post($postData, true);
    } else {
      $result = wp_insert_post($postData, true);
      if (!is_wp_error($result)) {
        $postId = (int)$result;
      }
    }

    if (is_wp_error($result)) {
      return $result;
    }

    update_post_meta($postId, 'limap_supplier_id_source', (string)$idSource);
    update_post_meta($postId, 'limap_supplier_payload', wp_json_encode($supplier));
    limap_sync_update_featured_image($postId, (string)($supplier['logo_url'] ?? ''), (string)($supplier['photo_cover_url'] ?? ''));

    return $postId;
  }

  function limap_sync_update_featured_image(int $postId, string $primaryUrl = '', string $fallbackUrl = ''): void
  {
    if ($postId <= 0) {
      return;
    }

    $imageUrl = '';
    foreach ([$primaryUrl, $fallbackUrl] as $candidate) {
      $candidate = trim($candidate);
      if ($candidate !== '' && preg_match('/^https?:\/\//i', $candidate)) {
        $imageUrl = $candidate;
        break;
      }
    }

    $previousUrl = (string)get_post_meta($postId, 'limap_featured_image_source_url', true);

    if ($imageUrl === '') {
      if (has_post_thumbnail($postId)) {
        delete_post_thumbnail($postId);
      }
      delete_post_meta($postId, 'limap_featured_image_source_url');
      return;
    }

    if ($previousUrl === $imageUrl && has_post_thumbnail($postId)) {
      return;
    }

    if (!function_exists('media_handle_sideload')) {
      require_once ABSPATH . 'wp-admin/includes/media.php';
      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $tmp = download_url($imageUrl);
    if (is_wp_error($tmp)) {
      return;
    }

    $path = parse_url($imageUrl, PHP_URL_PATH);
    $filename = is_string($path) && $path !== '' ? basename($path) : 'limap-image.jpg';
    if ($filename === '' || strpos($filename, '.') === false) {
      $filename = 'limap-image.jpg';
    }

    $file = [
      'name' => sanitize_file_name($filename),
      'tmp_name' => $tmp,
    ];

    $attachmentId = media_handle_sideload($file, $postId, null, [
      'post_title' => get_the_title($postId),
      'post_status' => 'inherit',
    ]);

    if (is_wp_error($attachmentId)) {
      @unlink($tmp);
      return;
    }

    $oldThumbId = (int)get_post_thumbnail_id($postId);
    set_post_thumbnail($postId, (int)$attachmentId);
    update_post_meta($postId, 'limap_featured_image_source_url', $imageUrl);

    if ($oldThumbId > 0 && $oldThumbId !== (int)$attachmentId) {
      wp_delete_attachment($oldThumbId, true);
    }
  }

  function limap_sync_render_supplier_content(array $supplier): string
  {
    $f = static function (string $key) use ($supplier): string {
      return trim((string)($supplier[$key] ?? ''));
    };

    $title = esc_html($f('name'));
    $type = esc_html($f('supplier_type'));
    $activityText = esc_html($f('activity_text'));
    $desc = wp_kses_post($f('description_long'));
    $short = esc_html($f('description_short'));
    $city = esc_html($f('city'));
    $address = esc_html(trim($f('address') . ' ' . $f('postal_code') . ' ' . $f('city') . ' ' . $f('country')));
    $phone = esc_html($f('phone'));
    $email = esc_html($f('email'));
    $website = esc_url($f('website'));
    $logo = esc_url($f('logo_url'));
    $facebook = esc_url($f('facebook_url') !== '' ? $f('facebook_url') : $f('facebook'));
    $instagram = esc_url($f('instagram_url') !== '' ? $f('instagram_url') : $f('instagram'));
    $linkedin = esc_url($f('linkedin_url') !== '' ? $f('linkedin_url') : $f('linkedin'));

    ob_start();
    ?>
    <div class="client-side-wrap limap-client-sync">
      <div class="left-col">
      <div class="client-logo-wrap">
        <?php if ($logo !== ''): ?>
        <img class="client-logo-main" src="<?php echo $logo; ?>" alt="<?php echo $title; ?>" loading="lazy" />
        <?php endif; ?>
      </div>
      <h1><?php echo $title; ?></h1>
      <?php if ($short !== ''): ?><p><?php echo $short; ?></p><?php endif; ?>
      <?php if ($desc !== ''): ?><div><?php echo $desc; ?></div><?php endif; ?>
      </div>
      <div class="right-col">
      <div class="project-info">
        <?php if ($type !== ''): ?><p><strong>Type:</strong> <?php echo $type; ?></p><?php endif; ?>
        <?php if ($activityText !== ''): ?><p><strong>Activités:</strong> <?php echo $activityText; ?></p><?php endif; ?>
        <p><strong>Ville:</strong> <?php echo $city; ?></p>
        <p><strong>Adresse:</strong> <?php echo $address; ?></p>
        <p><strong>Téléphone:</strong> <?php echo $phone; ?></p>
        <?php if ($email !== ''): ?><p><strong>Email:</strong> <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></p><?php endif; ?>
        <?php if ($website !== ''): ?><p><strong>Site web:</strong> <a href="<?php echo $website; ?>" target="_blank" rel="noopener"><?php echo $website; ?></a></p><?php endif; ?>
        <?php if ($facebook !== '' || $instagram !== '' || $linkedin !== ''): ?>
        <div>
          <strong style="display: block; margin-bottom: 0.8em;">Réseaux sociaux</strong>
          <div class="social-links">
          <?php if ($facebook !== ''): ?><a href="<?php echo $facebook; ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
          <?php if ($instagram !== ''): ?><a href="<?php echo $instagram; ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
          <?php if ($linkedin !== ''): ?><a href="<?php echo $linkedin; ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      </div>
    </div>
    <?php
    return (string)ob_get_clean();
  }
