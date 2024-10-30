<?php
/*
Controller name: Core
Controller description: Basic introspection methods
 */
if (!defined('ABSPATH')) {
	exit;
}

class RES_JSON_API_Core_Controller {
//$cache = new Cache();
	public function info() {
		global $res_json_api;
		global $res_version;
		$php = '';
		if (!empty($res_json_api->query->controller)) {
			return $res_json_api->controller_info($res_json_api->query->controller);
		} else {

			$dir = res_json_api_dir();
			global $res_cache;
			$url_md5 = md5('blog_info_' . $res_version);
			if ($res_cache->isCached($url_md5)) {
				//echo "CACHE";
				return $res_cache->retrieve($url_md5);
			} else {
				if (file_exists("$dir/res-json-api.php")) {
					$php = file_get_contents("$dir/res-json-api.php");
				} else {
					// Check one directory up, in case res-json-api.php was moved
					$dir = dirname($dir);
					if (file_exists("$dir/res-json-api.php")) {
						$php = file_get_contents("$dir/res-json-api.php");
					}
				}
				if (preg_match('/^\s*Version:\s*(.+)$/m', $php, $matches)) {
					$version = $matches[1];
				} else {
					$version = '(Unknown)';
				}
				$active_controllers = explode(',', get_option('res_json_api_controllers', 'core'));
				$controllers = array_intersect($res_json_api->get_controllers(), $active_controllers);
				$result = array(
					'res_json_api_version' => $res_version,
					'controllers' => array_values($controllers),
				);

				$res_cache->eraseExpired();
				$res_cache->store($url_md5, $result, 300);
				return $result;
			}
		}
	}


	public function get_posts() {
		global $res_json_api;
		global $res_cache;
		global $res_version;
		$url = parse_url($_SERVER['REQUEST_URI']);
		$url_md5 = md5($_SERVER['REQUEST_URI'] . $res_version);
		if ($res_cache->isCached($url_md5)) {
//echo("Cache");
			return $res_cache->retrieve($url_md5);
		} else {
//echo "NO CACHE";
			$defaults = array(
				'ignore_sticky_posts' => true,
			);
			$query = wp_parse_args($url['query']);
			unset($query['res-json']);
			unset($query['post_status']);

			$query = array_merge($defaults, $query);
			$posts = $res_json_api->introspector->get_posts($query);
			$result = $this->posts_result($posts);
			$result['query'] = $query;
			$res_cache->eraseExpired();
			$res_cache->store($url_md5, $result, 300);
			return $result;
		}
	}

	public function get_post() {
		global $res_json_api, $post;
		$post = $res_json_api->introspector->get_current_post();
		if ($post) {
			$previous = get_adjacent_post(false, '', true);
			$next = get_adjacent_post(false, '', false);
			$response = array(
				'post' => new RES_JSON_API_Post($post),
			);
			if ($previous) {
				$response['previous_url'] = get_permalink($previous->ID);
			}
			if ($next) {
				$response['next_url'] = get_permalink($next->ID);
			}
			return $response;
		} else {
			$res_json_api->error("Not found.");
		}
	}

	/*public function get_page() {
		global $res_json_api;
		extract($res_json_api->query->get(array('id', 'slug', 'page_id', 'page_slug', 'children')));
		if ($id || $page_id) {
			if (!$id) {
				$id = $page_id;
			}
			$posts = $res_json_api->introspector->get_posts(array(
				'page_id' => $id,
			));
		} else if ($slug || $page_slug) {
			if (!$slug) {
				$slug = $page_slug;
			}
			$posts = $res_json_api->introspector->get_posts(array(
				'pagename' => $slug,
			));
		} else {
			$res_json_api->error("Include 'id' or 'slug' var in your request.");
		}

// Workaround for https://core.trac.wordpress.org/ticket/12647
		if (empty($posts)) {
			$url = $_SERVER['REQUEST_URI'];
			$parsed_url = parse_url($url);
			$path = $parsed_url['path'];
			if (preg_match('#^http://[^/]+(/.+)$#', get_bloginfo('url'), $matches)) {
				$blog_root = $matches[1];
				$path = preg_replace("#^$blog_root#", '', $path);
			}
			if (substr($path, 0, 1) == '/') {
				$path = substr($path, 1);
			}
			$posts = $res_json_api->introspector->get_posts(array('pagename' => $path));
		}

		if (count($posts) == 1) {
			if (!empty($children)) {
				$res_json_api->introspector->attach_child_posts($posts[0]);
			}
			return array(
				'page' => $posts[0],
			);
		} else {
			$res_json_api->error("Not found.");
		}
	}

	public function get_date_posts() {
		global $res_json_api;
		if ($res_json_api->query->date) {
			$date = preg_replace('/\D/', '', $res_json_api->query->date);
			if (!preg_match('/^\d{4}(\d{2})?(\d{2})?$/', $date)) {
				$res_json_api->error("Specify a date var in one of 'YYYY' or 'YYYY-MM' or 'YYYY-MM-DD' formats.");
			}
			$request = array('year' => substr($date, 0, 4));
			if (strlen($date) > 4) {
				$request['monthnum'] = (int) substr($date, 4, 2);
			}
			if (strlen($date) > 6) {
				$request['day'] = (int) substr($date, 6, 2);
			}
			$posts = $res_json_api->introspector->get_posts($request);
		} else {
			$res_json_api->error("Include 'date' var in your request.");
		}
		return $this->posts_result($posts);
	}

	public function get_category_posts() {
		global $res_json_api;
		$category = $res_json_api->introspector->get_current_category();
		if (!$category) {
			$res_json_api->error("Not found.");
		}
		$posts = $res_json_api->introspector->get_posts(array(
			'cat' => $category->id,
		));
		return $this->posts_object_result($posts, $category);
	}

	public function get_tag_posts() {
		global $res_json_api;
		$tag = $res_json_api->introspector->get_current_tag();
		if (!$tag) {
			$res_json_api->error("Not found.");
		}
		$posts = $res_json_api->introspector->get_posts(array(
			'tag' => $tag->slug,
		));
		return $this->posts_object_result($posts, $tag);
	}

	public function get_author_posts() {
		global $res_json_api;
		$author = $res_json_api->introspector->get_current_author();
		if (!$author) {
			$res_json_api->error("Not found.");
		}
		$posts = $res_json_api->introspector->get_posts(array(
			'author' => $author->id,
		));
		return $this->posts_object_result($posts, $author);
	}
*/
	public function get_search_results() {
		global $res_json_api;
		if ($res_json_api->query->search) {
			$posts = $res_json_api->introspector->get_posts(array(
				's' => $res_json_api->query->search,
			));
		} else {
			$res_json_api->error("Include 'search' var in your request.");
		}
		return $this->posts_result($posts);
	}
/*
	public function get_date_index() {
		global $res_json_api;
		$permalinks = $res_json_api->introspector->get_date_archive_permalinks();
		$tree = $res_json_api->introspector->get_date_archive_tree($permalinks);
		return array(
			'permalinks' => $permalinks,
			'tree' => $tree,
		);
	}

	public function get_category_index() {
		global $res_json_api;
		$args = null;
		if (!empty($res_json_api->query->parent)) {
			$args = array(
				'parent' => $res_json_api->query->parent,
			);
		}
		$categories = $res_json_api->introspector->get_categories($args);
		return array(
			'count' => count($categories),
			'categories' => $categories,
		);
	}

	public function get_tag_index() {
		global $res_json_api;
		$tags = $res_json_api->introspector->get_tags();
		return array(
			'count' => count($tags),
			'tags' => $tags,
		);
	}

	public function get_author_index() {
		global $res_json_api;
		$authors = $res_json_api->introspector->get_authors();
		return array(
			'count' => count($authors),
			'authors' => array_values($authors),
		);
	}

	public function get_page_index() {
		global $res_json_api;
		$pages = array();
		$post_type = $res_json_api->query->post_type ? $res_json_api->query->post_type : 'page';

// Thanks to blinder for the fix!
		$numberposts = empty($res_json_api->query->count) ? -1 : $res_json_api->query->count;
		$wp_posts = get_posts(array(
			'post_type' => $post_type,
			'post_parent' => 0,
			'order' => 'ASC',
			'orderby' => 'menu_order',
			'numberposts' => $numberposts,
		));
		foreach ($wp_posts as $wp_post) {
			$pages[] = new RES_JSON_API_Post($wp_post);
		}
		foreach ($pages as $page) {
			$res_json_api->introspector->attach_child_posts($page);
		}
		return array(
			'pages' => $pages,
		);
	}

	public function get_nonce() {
		global $res_json_api;
		extract($res_json_api->query->get(array('controller', 'method')));
		if ($controller && $method) {
			$controller = strtolower($controller);
			if (!in_array($controller, $res_json_api->get_controllers())) {
				$res_json_api->error("Unknown controller '$controller'.");
			}
			require_once $res_json_api->controller_path($controller);
			if (!method_exists($res_json_api->controller_class($controller), $method)) {
				$res_json_api->error("Unknown method '$method'.");
			}
			$nonce_id = $res_json_api->get_nonce_id($controller, $method);
			return array(
				'controller' => $controller,
				'method' => $method,
				'nonce' => wp_create_nonce($nonce_id),
			);
		} else {
			$res_json_api->error("Include 'controller' and 'method' vars in your request.");
		}
	}

	protected function get_object_posts($object, $id_var, $slug_var) {
		global $res_json_api;
		$object_id = "{$type}_id";
		$object_slug = "{$type}_slug";
		extract($res_json_api->query->get(array('id', 'slug', $object_id, $object_slug)));
		if ($id || $$object_id) {
			if (!$id) {
				$id = $$object_id;
			}
			$posts = $res_json_api->introspector->get_posts(array(
				$id_var => $id,
			));
		} else if ($slug || $$object_slug) {
			if (!$slug) {
				$slug = $$object_slug;
			}
			$posts = $res_json_api->introspector->get_posts(array(
				$slug_var => $slug,
			));
		} else {
			$res_json_api->error("No $type specified. Include 'id' or 'slug' var in your request.");
		}
		return $posts;
	}
*/
	protected function posts_result($posts) {
		global $wp_query;
		return array(
			'count' => count($posts),
			'count_total' => (int) $wp_query->found_posts,
			'pages' => $wp_query->max_num_pages,
			'posts' => $posts,
		);
	}

	protected function posts_object_result($posts, $object) {
		global $wp_query;
// Convert something like "RES_JSON_API_Category" into "category"
		$object_key = strtolower(substr(get_class($object), 9));
		return array(
			'count' => count($posts),
			'pages' => (int) $wp_query->max_num_pages,
			$object_key => $object,
			'posts' => $posts,
		);
	}

	public function res_get_blog_info() {

		$blog_info = array(
			'blogcats' => $this->get_all_cat(),
			//'recents_posts' => $this -> get_posts(),
			'info' => $this->info(),
		);

		return $blog_info;

	}

	public function get_all_cat() {

		global $res_cache;
		global $res_version;

		$url_md5 = md5('get_all_cat_' . $res_version);
		if ($res_cache->isCached($url_md5)) {
			return $res_cache->retrieve($url_md5);
		} else {
			$pr = get_option('res_disallowed_cats');
//var_dump($pr);
			//$pr = explode(',', $pr);
			$args = array(
				'orderby' => 'name',
				'parent' => 0,
				'exclude' => $pr,
			);

			$res_cache->eraseExpired();
			$result = get_categories($args);
			$res_cache->store($url_md5, $result, 900);

			return $result;
		}

	}

	public function get_images() {
		global $res_json_api, $post;
		$post = $res_json_api->introspector->get_current_post();
		$totalItem = array();
		$postContent = $post->post_content;
		preg_match_all('/<img[^>]*src=[\"|\'](.*)[\"|\']/Ui', $postContent, $out);
		$totalItem['images'] = $out[1];

		return $totalItem;

	}

}

?>
