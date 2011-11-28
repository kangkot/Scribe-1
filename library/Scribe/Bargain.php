<?php

class Scribe_Bargain extends Bargain_Extra {
	public $relative_prefix = '';
	public $top_header = 2;
	public $headings = array();

	protected $header_num = array(0);

	protected function _doHeaders_id($attr, $title) {
		if (empty($attr)) {
			$attr = strtolower(str_replace(' ', '_', $title));
			$attr = preg_replace('/[^\w]/i', '', $attr);
		}
		return $attr;
	}

	public function _doHeaders_callback_setext($matches) {
		if ($matches[3] == '-' && preg_match('{^- }', $matches[1]))
			return $matches[0];
		$level = $matches[3]{0} == '=' ? 1 : 2;
		$title = $this->runSpanGamut($matches[1]);
		$id    = $this->_doHeaders_id($matches[2], $title);
		$real  = $level - $this->top_header;
		$this->headings[$title] = $real + 1;

		if ($real !== 0) {
			$this->header_num = array_slice($this->header_num, 0, $real + 1);
			if (!isset($this->header_num[$real])) {
				$this->header_num[$real] = 1;
			}
			else {
				$this->header_num[$real]++;
			}
			$title = implode('.', array_slice($this->header_num, 1)) . ' ' . $title;
		}
		$block = "<h$level id=\"$id\">". $title ."</h$level>";

		return "\n" . $this->hashBlock($block) . "\n\n";
	}
	public function _doHeaders_callback_atx($matches) {
		$level = strlen($matches[1]);
		$title = $this->runSpanGamut($matches[2]);

		if (!isset($matches[3]))
			$matches[3] = false;

		$id    = $this->_doHeaders_id($matches[3], $title);
		$real  = $level - $this->top_header;
		$this->headings[$title] = $real + 1;

		if ($real !== 0) {
			$this->header_num = array_slice($this->header_num, 0, $real + 1);
			if (!isset($this->header_num[$real])) {
				$this->header_num[$real] = 1;
			}
			else {
				$this->header_num[$real]++;
			}
			$title = implode('.', array_slice($this->header_num, 1)) . ' ' . $title;
		}

		$block = "<h$level id=\"$id\">". $title ."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}

	protected function _doAnchors_reference_callback($matches) {
		$whole_match =  $matches[1];
		$link_text   =  $matches[2];
		$link_id     =& $matches[3];

		if ($link_id == "") {
			# for shortcut links like [this][] or [this].
			$link_id = $link_text;
		}
		
		# lower-case and turn embedded newlines into spaces
		$link_id = strtolower($link_id);
		$link_id = preg_replace('{[ ]?\n}', ' ', $link_id);

		if (isset($this->urls[$link_id])) {
			$url = $this->urls[$link_id];
			$url = $this->encodeAttribute($url);
			if ($url[0] === '/' && $url[1] !== '/') {
				$url = $this->relative_prefix . $url;
			}
			
			$result = "<a href=\"$url\"";
			if ( isset( $this->titles[$link_id] ) ) {
				$title = $this->titles[$link_id];
				$title = $this->encodeAttribute($title);
				$result .=  " title=\"$title\"";
			}
		
			$link_text = $this->runSpanGamut($link_text);
			$result .= ">$link_text</a>";
			$result = $this->hashPart($result);
		}
		else {
			$result = $whole_match;
		}
		return $result;
	}
	protected function _doAnchors_inline_callback($matches) {
		$whole_match	=  $matches[1];
		$link_text		=  $this->runSpanGamut($matches[2]);
		$url			=  $matches[3] == '' ? $matches[4] : $matches[3];
		$title			=& $matches[7];

		$url = $this->encodeAttribute($url);
		if ($url[0] === '/' && $url[1] !== '/') {
			$url = $this->relative_prefix . $url;
		}

		$result = "<a href=\"$url\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\"";
		}
		
		$link_text = $this->runSpanGamut($link_text);
		$result .= ">$link_text</a>";

		return $this->hashPart($result);
	}
}