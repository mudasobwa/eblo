<?php

namespace Mudasobwa\Eblo;

require_once 'vendor/autoload.php';

class Markright
{
	private $rules;

	public function __construct($rules = NULL) {
		$this->rules = file_exists($rules) ? \Spyc::YAMLLoad($rules) :
			[
				'/(?<=\W)\*\*(.*?)\*\*(?=\W)/smxu'		=> '<strong>\1</strong>',
				'/(?<=\W)\*(.*?)\*(?=\W)/smxu'				=> '<b>\1</b>',
				'/(?<=\W)__(.*?)__(?=\W)/smxu'				=> '<em>\1</em>',
				'/(?<=\W)_(.*?)_(?=\W)/smxu'					=> '<i>\1</i>',
				'/(?<=\W)✓(.*?)✓(?=\W)/smxu'					=> '<span class="as-is">\1</span>',
				'/(?<=\W)`(.*?)`(?=\W)/smxu'					=> '<code>\1</code>',
				'/(?<=\W)\+(.*?)\+(?=\W)/smxu'				=> '<small>\1</small>',
				'/(?<=\W)↓(.*?)↓(?=\W)/smxu'					=> '<small>\1</small>',

				'/\A([^<].*?)$(?=.{2})/msxu'						=> '<h1>\1</h1>',
				'/\A\s*([^<].*?)$(?=\Z)/mxu'					=> '<div class="schild">\1</div>',

				'/        +\s*(.*?)$/msxu'						=> '<p class="epigraph">\1</p>',

				'/✎\s*(\w+)/u'												=> '<span style="white-space: nowrap;"><a href="http://\1.livejournal.com/profile?mode=full"><img src="http://l-stat.livejournal.com/img/userinfo.gif" alt="[info]" style="border: 0pt none; vertical-align: bottom; padding-right: 1px;" height="17" width="17"></a><a href="http://\1.livejournal.com/?style=mine"><b>\1</b></a></span>',

				'/http:\/\/youtu\.be\/(\w+)(?:\?t=(\d+)s)?/' =>		// http://youtu.be/SAJ_TzLqy1U?t=6s
						'<iframe class="youtube" width="560" height="315" src="http://www.youtube.com/embed/\1" frameborder="0" allowfullscreen></iframe>',
				'/http:\/\/www\.youtube\.com\/(?:watch\?v=|v\/)(\w+)\S*/' =>		// http://www.youtube.com/watch?v=SAJ_TzLqy1U
						'<iframe class="youtube" width="560" height="315" src="http://www.youtube.com/embed/\1" frameborder="0" allowfullscreen></iframe>',
				'/^(https?:\/\/\S+)\s*(?:\Z|$)/smux'			=>				// Standalone images w/out title
						'<img style="float:left; margin: 0 1em 1em 0;" src="\1"/>',
				'/^(https?:\/\/\S+)\s+(.*?)(?=\Z|\R{2,})/smux'	=>				// Standalone images w/title
						'<figure><img src="\1"/><figcaption><p>\2</p></figcaption></figure>',

					'/\[(.*?)\]\((https?:\/\/\S+?)\)/u'		=> '<a href="\2">\1</a>',
					'/(\S+)\s*\((https?:\/\/\S+?)\)/u'		=> '<a href="\2">\1</a>',
				'/(mailto:(\S+))/u'										=> '<a href="\1">✉ \2</a>',

				'/^\s*§(\d+)\s+(.*?)$/umsx'						=> '<h\1>\2</h\1>',

				'/  +\s*$/mxu'												=> '<br>',
					'/\s+—/mxu'														=> ' —',
				'/^[-—\s]{2,}$/sumx'									=> '<hr>',

						'/(\A|\R\R+)([—\p{L}\p{N}].*?)(?=\Z|\R\R+)/smxu' => '\1<p>\2</p>' // goes last

			];
	}

	private function blockquotes($input) {
		return preg_replace_callback('/^[ ]{4,6}([^ ].*?)(?=\z|\R\R+)/smxu', function($mch) {
			return '<blockquote>' . preg_replace_callback(
					'/[ ]*✍\s+(([^,]*)(?:,\s+|\s+)(\S+))/smxu',
					function($mch1) {
						return '<p><small>'.
							(strpos($mch1[3], 'http') === 0 ? "<a href='{$mch1[3]}'>{$mch1[2]}</a>" : "{$mch1[1]}")
						.'</small></p>';
					},
					preg_replace('/^[ ]{4,}/smxu', '', $mch[1])
			) . '</blockquote>';
		}, $input);
	}

	private function datadefs($input) {
		return preg_replace_callback('/(^([▶▷])\s*([^—]+)—(.*?))+(?=\z|\R\R+)/smxu', function($mch) {
			return	($mch[2] === '▶' ? '<dl>' : '<dl class="dl-horizontal">') .
						preg_replace('/^[▶▷]\s*([^—]+)\s+—\s+(.*?)$/msux', '<dt>\1</dt><dd>\2</dd>', $mch[0]) .
					'</dl>';
		}, $input);
	}

	private function tables($input) {
		return preg_replace_callback('/((\t+)(.*?))+(?=\z|\R\R+)/smxu', function($mch) {
			return	'<table>' .
						preg_replace('/^(.*)$/mu', '<tr>\1</tr>', preg_replace('/\t+([^\t\n]*)/xu', '<td>\1</td>', $mch[0])) .
					'</table>';
		}, $input);
	}

	private function lists($input) {
		return preg_replace_callback('/(^\s*([*•◦])\s*(.*?)$)+(?=\z|\R\R+)/smxu', function($mch) {
			return	($mch[2] === '◦' ? '<ol>' : '<ul>') .
						preg_replace('/^[•◦]\s*(.*?)$/msux', '<li>\1</li>', $mch[0]) .
					($mch[2] === '◦' ? '</ol>' : '</ul>');
		}, $input);
	}

	public function parse($input) {
		$input = $this->blockquotes($input);
		$input = $this->lists($input);
		$input = $this->datadefs($input);
		$input = $this->tables($input);

		foreach ($this->rules as $re => $subst) {
			$input = preg_replace($re, $subst, $input);
		}
		return $input;
	}

	public static function yo($input) {
		return (new Markright)->parse($input);
	}

}
 
