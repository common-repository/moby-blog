<?php
if (!defined('ABSPATH')) {
    exit;
}

class RES_JSON_API_Post
{

    // Note:
    //   RES_JSON_API_Post objects must be instantiated within The Loop.

    public $id; // Integer
    public $type; // String
    //public $slug; // String
    public $url; // String
    public $status; // String ("draft", "published", or "pending")
    public $title; // String
    public $title_plain; // String
    //public $content; // String (modified by read_more query var)
    public $excerpt; // String
    public $date; // String (modified by date_format query var)
    public $date_unix;
    public $modified; // String (modified by date_format query var)
    public $categories; // Array of objects
    public $tags; // Array of objects
    public $author; // Object
    //public $comments; // Array of objects
    public $attachments; // Array of objects
    //public $comment_count; // Integer
    //public $comment_status; // String ("open" or "closed")
    public $thumbnail; // String
    //public $custom_fields; // Object (included by using custom_fields query var)

    public function RES_JSON_API_Post($wp_post = null)
    {
        if (!empty($wp_post)) {
            $this->import_wp_object($wp_post);
        }
        do_action("res_json_api_{$this->type}_constructor", $this);
    }

    public function create($values = null)
    {
        unset($values['id']);
        if (empty($values) || empty($values['title'])) {
            $values = array(
                'title'   => 'Untitled',
                'content' => '',
            );
        }
        return $this->save($values);
    }

    public function update($values)
    {
        $values['id'] = $this->id;
        return $this->save($values);
    }

    public function save($values = null)
    {
        global $res_json_api, $user_ID;

        $wp_values = array();

        if (!empty($values['id'])) {
            $wp_values['ID'] = $values['id'];
        }

        if (!empty($values['type'])) {
            $wp_values['post_type'] = $values['type'];
        }

        if (!empty($values['status'])) {
            $wp_values['post_status'] = $values['status'];
        }

        if (!empty($values['title'])) {
            $wp_values['post_title'] = $values['title'];
        }

        if (!empty($values['content'])) {
            $wp_values['post_content'] = $values['content'];
        }

        if (!empty($values['author'])) {
            $author                   = $res_json_api->introspector->get_author_by_login($values['author']);
            $wp_values['post_author'] = $author->id;
        }

        if (isset($values['categories'])) {
            $categories = explode(',', $values['categories']);
            foreach ($categories as $category_slug) {
                $category_slug = trim($category_slug);
                $category      = $res_json_api->introspector->get_category_by_slug($category_slug);
                if (empty($wp_values['post_category'])) {
                    $wp_values['post_category'] = array($category->id);
                } else {
                    array_push($wp_values['post_category'], $category->id);
                }
            }
        }

        if (isset($values['tags'])) {
            $tags = explode(',', $values['tags']);
            foreach ($tags as $tag_slug) {
                $tag_slug = trim($tag_slug);
                if (empty($wp_values['tags_input'])) {
                    $wp_values['tags_input'] = array($tag_slug);
                } else {
                    array_push($wp_values['tags_input'], $tag_slug);
                }
            }
        }

        if (isset($wp_values['ID'])) {
            $this->id = wp_update_post($wp_values);
        } else {
            $this->id = wp_insert_post($wp_values);
        }

        if (!empty($_FILES['attachment'])) {
            include_once ABSPATH . '/wp-admin/includes/file.php';
            include_once ABSPATH . '/wp-admin/includes/media.php';
            include_once ABSPATH . '/wp-admin/includes/image.php';
            $attachment_id       = media_handle_upload('attachment', $this->id);
            $this->attachments[] = new RES_JSON_API_Attachment($attachment_id);
            unset($_FILES['attachment']);
        }

        $wp_post = get_post($this->id);
        $this->import_wp_object($wp_post);

        return $this->id;
    }

    public function import_wp_object($wp_post)
    {
        global $res_json_api, $post;
        $date_format = $res_json_api->query->date_format;
        $this->id    = (int) $wp_post->ID;
        setup_postdata($wp_post);
        $this->set_value('type', $wp_post->post_type);
        //$this->set_value('slug', $wp_post->post_name);
        $this->set_value('url', get_permalink($this->id));
        $this->set_value('status', $wp_post->post_status);
        $this->set_value('title', get_the_title($this->id));
        $this->set_value('title_plain', strip_tags(@$this->title));
        //$this->set_content_value();
        $this->set_value('excerpt', apply_filters('the_excerpt', get_the_excerpt()));
        $this->set_value('date', get_the_time($date_format));
        $this->set_value('date_unix', get_the_time('U'));
        $this->set_value('modified', date($date_format, strtotime($wp_post->post_modified)));
        $this->set_categories_value();
        $this->set_tags_value();
        $this->set_author_value($wp_post->post_author);
        //$this->set_comments_value();
        $this->set_attachments_value();
        //$this->set_value('comment_count', (int) $wp_post->comment_count);
        //$this->set_value('comment_status', $wp_post->comment_status);
        $this->set_thumbnail_value();
        //$this->set_custom_fields_value();
        //$this->set_custom_taxonomies($wp_post->post_type);
        do_action("res_json_api_import_wp_post", $this, $wp_post);
    }

    public function set_value($key, $value)
    {
        global $res_json_api;
        if ($res_json_api->include_value($key)) {
            $this->$key = $value;
        } else {
            unset($this->$key);
        }
    }

    public function set_content_value()
    {
        global $res_json_api;
        if ($res_json_api->include_value('content')) {
            $content       = get_the_content($res_json_api->query->read_more);
            $content       = apply_filters('the_content', $content);
            $content       = str_replace(']]>', ']]&gt;', $content);
            $this->content = $content;
        } else {
            unset($this->content);
        }
    }

    public function set_categories_value()
    {
        global $res_json_api;
        if ($res_json_api->include_value('categories')) {
            $this->categories = array();
            if ($wp_categories = get_the_category($this->id)) {
                foreach ($wp_categories as $wp_category) {
                    $category = new RES_JSON_API_Category($wp_category);
                    if ($category->id == 1 && $category->slug == 'uncategorized') {
                        // Skip the 'uncategorized' category
                        continue;
                    }
                    $this->categories[] = $category;
                }
            }
        } else {
            unset($this->categories);
        }
    }

    public function set_tags_value()
    {
        global $res_json_api;
        if ($res_json_api->include_value('tags')) {
            $this->tags = array();
            if ($wp_tags = get_the_tags($this->id)) {
                foreach ($wp_tags as $wp_tag) {
                    $this->tags[] = new RES_JSON_API_Tag($wp_tag);
                }
            }
        } else {
            unset($this->tags);
        }
    }

    public function set_author_value($author_id)
    {
        global $res_json_api;
        if ($res_json_api->include_value('author')) {
            $this->author = new RES_JSON_API_Author($author_id);
        } else {
            unset($this->author);
        }
    }

    public function set_comments_value()
    {
        global $res_json_api;
        if ($res_json_api->include_value('comments')) {
            $this->comments = $res_json_api->introspector->get_comments($this->id);
        } else {
            unset($this->comments);
        }
    }

    public function set_attachments_value()
    {
        global $res_json_api;
        if ($res_json_api->include_value('attachments')) {
            $atts = $res_json_api->introspector->get_attachments($this->id, 1);
            $att  = $atts[0];
            if (count($att->images == 0)) {
                $content = get_the_content($res_json_api->query->read_more);
                $content = apply_filters('the_content', $content);
                $content = str_replace(']]>', ']]&gt;', $content);

                preg_match("/https?:\/\/\S+(?:png|jpg|jpeg)/", $content, $cover_img);
                if (isset($cover_img)) {
                	$att->url_derived = $cover_img[0];
                	$atts[0] = $att;
                }
            }

            $this->attachments = $atts;
        } else {
            unset($this->attachments);
        }
    }

    public function set_thumbnail_value()
    {
        global $res_json_api;
        if (!$res_json_api->include_value('thumbnail') ||
            !function_exists('get_post_thumbnail_id')) {
            unset($this->thumbnail);
            return;
        }
        $attachment_id = get_post_thumbnail_id($this->id);
        if (!$attachment_id) {
            unset($this->thumbnail);
            return;
        }
        $thumbnail_size         = $this->get_thumbnail_size();
        $this->thumbnail_size   = $thumbnail_size;
        $attachment             = $res_json_api->introspector->get_attachment($attachment_id);
        $image                  = $attachment->images[$thumbnail_size];
        $this->thumbnail        = $image->url;
        $this->thumbnail_images = $attachment->images;
    }

    public function set_custom_fields_value()
    {
        global $res_json_api;
        if ($res_json_api->include_value('custom_fields')) {
            $wp_custom_fields    = get_post_custom($this->id);
            $this->custom_fields = new stdClass();
            if ($res_json_api->query->custom_fields) {
                $keys = explode(',', $res_json_api->query->custom_fields);
            }
            foreach ($wp_custom_fields as $key => $value) {
                if ($res_json_api->query->custom_fields) {
                    if (in_array($key, $keys)) {
                        $this->custom_fields->$key = $wp_custom_fields[$key];
                    }
                } else if (substr($key, 0, 1) != '_') {
                    $this->custom_fields->$key = $wp_custom_fields[$key];
                }
            }
        } else {
            unset($this->custom_fields);
        }
    }

    public function set_custom_taxonomies($type)
    {
        global $res_json_api;
        $taxonomies = get_taxonomies(array(
            'object_type' => array($type),
            'public'      => true,
            '_builtin'    => false,
        ), 'objects');
        foreach ($taxonomies as $taxonomy_id => $taxonomy) {
            $taxonomy_key = "taxonomy_$taxonomy_id";
            if (!$res_json_api->include_value($taxonomy_key)) {
                continue;
            }
            $taxonomy_class      = $taxonomy->hierarchical ? 'RES_JSON_API_Category' : 'RES_JSON_API_Tag';
            $terms               = get_the_terms($this->id, $taxonomy_id);
            $this->$taxonomy_key = array();
            if (!empty($terms)) {
                $taxonomy_terms = array();
                foreach ($terms as $term) {
                    $taxonomy_terms[] = new $taxonomy_class($term);
                }
                $this->$taxonomy_key = $taxonomy_terms;
            }
        }
    }

    public function get_thumbnail_size()
    {
        global $res_json_api;
        if ($res_json_api->query->thumbnail_size) {
            return $res_json_api->query->thumbnail_size;
        } else if (function_exists('get_intermediate_image_sizes')) {
            $sizes = get_intermediate_image_sizes();
            if (in_array('post-thumbnail', $sizes)) {
                return 'post-thumbnail';
            }
        }
        return 'thumbnail';
    }

}
