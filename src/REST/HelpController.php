<?php

declare(strict_types=1);

namespace BaseMgmt\REST;

use BaseMgmt\Modules\Help\HelpRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Help / Knowledge Base REST endpoints.
 *
 * All require a valid camp session (read-only for camp).
 *
 * GET /bm/v1/panel/help              – list published articles (with optional filters)
 * GET /bm/v1/panel/help/{id}         – single article
 */
final class HelpController extends BaseController {

	public function register_routes(): void {
		$auth = fn(WP_REST_Request $r) => $this->require_session($r);

		register_rest_route(self::NAMESPACE, '/panel/help', [
			'methods'             => 'GET',
			'callback'            => [$this, 'list_articles'],
			'permission_callback' => $auth,
		]);

		register_rest_route(self::NAMESPACE, '/panel/help/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_article'],
			'permission_callback' => $auth,
		]);
	}

	// ── Handlers ──────────────────────────────────────────────────────────────

	public function list_articles(WP_REST_Request $request): WP_REST_Response {
		$filters = ['status' => 'published'];

		$type     = sanitize_key($request->get_param('type')     ?? '');
		$category = sanitize_text_field($request->get_param('category') ?? '');
		$search   = sanitize_text_field($request->get_param('search')   ?? '');
		$pinned   = (bool) $request->get_param('pinned');

		if ( $type )     $filters['type']      = $type;
		if ( $category ) $filters['category']  = $category;
		if ( $search )   $filters['search']    = $search;
		if ( $pinned )   $filters['is_pinned'] = true;

		$articles   = HelpRepository::get_all($filters);
		$categories = HelpRepository::get_categories();

		return new WP_REST_Response([
			'articles'   => array_map([$this, 'format_article'], $articles),
			'categories' => $categories,
			'types'      => HelpRepository::TYPES,
		]);
	}

	public function get_article(WP_REST_Request $request): WP_REST_Response {
		$id      = (int) $request->get_param('id');
		$article = HelpRepository::get($id);

		if ( ! $article || $article->status !== 'published' ) {
			return $this->error('not_found', __('Wpis nie znaleziony.', 'basemgmt'), 404);
		}

		return new WP_REST_Response(['article' => $this->format_article($article)]);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function format_article(object $art): array {
		return [
			'id'         => (int) $art->id,
			'title'      => $art->title,
			'excerpt'    => $art->excerpt,
			'content'    => $art->content,
			'category'   => $art->category,
			'type'       => $art->type,
			'type_label' => HelpRepository::TYPES[$art->type] ?? $art->type,
			'is_pinned'  => (bool) $art->is_pinned,
			'is_alarm'   => (bool) $art->is_alarm,
			'sort_order' => (int) $art->sort_order,
		];
	}
}
