<?php

class Scribe_Controller_Page {
	public static function view($page = 'index') {
		$sanitized = preg_replace('/[^\w-]+/i', '', $page);
		if ($sanitized !== $page) {
			Scribe_Router::redirect('/' . $sanitized);
		}
		$converter = null;
		$path = Scribe::$path . '/pages/' . $page;

		if (file_exists($path . '.md')) {
			$converter = new Scribe_Bargain();
			$converter->relative_prefix = Scribe::$url;
			$path = $path . '.md';
		}

		if (!file_exists($path)) {
			throw new Scribe_Response_404('Page does not exist');
		}

		$content = file($path);
		$content = implode('', $content);
		$content = $converter->transform($content);
		$headings = self::get_toc($converter->headings);

		self::render($content, $headings);
	}

	protected static function render($content, $toc) {
		$title = $toc[0]->name;

		require_once(Scribe::$path . '/template/header.php');
		echo $content;
		require_once(Scribe::$path . '/template/footer.php');
	}

	public static function print_toc($toc) {
		self::print_toc_level($toc[0]->children);
	}

	protected static function print_toc_level($toc) {
		echo '<ul>';
		foreach ($toc as $node) {
			$slug = strtolower(str_replace(' ', '_', $node->name));
			$slug = preg_replace('/[^\w]/i', '', $slug);
			echo '<li><a href="#' . $slug . '">' . $node->name . '</a>';
			if (!empty($node->children)) {
				self::print_toc_level($node->children);
			}
		}
		echo '</ul>';
	}

	protected static function get_toc($headings, $content = '') {
		$prev = null;
		$root = new Page_Node('root', 0);
		foreach ($headings as $title => $level) {
			$node = new Page_Node($title, $level);

			if ($level === 1) {
				$node->parent = &$root;
				$root->children[] = $node;
				$prev = $node;
				continue;
			}

			if ($prev === null) {
				continue;
			}

			if ($level === $prev->level) {
				$node->parent = $prev->parent;
			}
			elseif ($level < $prev->level) {
				$node->parent = $prev->parent->parent;
			}
			elseif ($level > $prev->level) {
				$node->parent = $prev;
			}
			$node->parent->add_child($node);

			$prev = $node;
		}

		return $root->children;
	}
}

class Page_Node {
	public $parent = null;
	public $children = array();

	public $name;
	public $level;

	public function __construct($name, $level) {
		$this->name = $name;
		$this->level = $level;
	}

	public function add_child($node) {
		$this->children[] = $node;
	}
}