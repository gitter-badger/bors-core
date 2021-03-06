<?php

// http://www.linuxjournal.com/article/7870
// 

class tex_render
{
	var $LATEX_PATH = "/usr/local/bin/latex";
	var $DVIPS_PATH = "/usr/local/bin/dvips";
	var $CONVERT_PATH = "/usr/local/bin/convert";

	var $TMP_DIR = "/usr/home/barik/public_html/gehennom/lj/tmp";
	var $CACHE_DIR = "/usr/home/barik/public_html/gehennom/lj/cache";

	var $URL_PATH = "http://www.barik.net/lj/cache";

	function wrap($thunk)
	{
		return <<<EOS
\documentclass[10pt]{article}

% add additional packages here
\usepackage{amsmath}
\usepackage{amsfonts}
\usepackage{amssymb}
\usepackage{pst-plot}
\usepackage{color}

\pagestyle{empty}
\begin{document}
$thunk
\end{document}
EOS;
	}

	function render_latex($thunk, $hash)
	{

	$thunk = $this->wrap($thunk);

	$current_dir = getcwd();
	chdir($this->TMP_DIR);

	// create temporary LaTeX file
	$fp = fopen($this->TMP_DIR . "/$hash.tex", "w+");
	fputs($fp, $thunk);
	fclose($fp);

	// run LaTeX to create temporary DVI file
	$command = $this->LATEX_PATH . " --interaction=nonstopmode " . $hash . ".tex";
	exec($command);

	// run dvips to create temporary PS file
	$command = $this->DVIPS_PATH . " -E $hash" . ".dvi -o " . "$hash.ps";
	exec($command);

	// run PS file through ImageMagick to
	// create PNG file
	$command = $this->CONVERT_PATH . " -density 120 $hash.ps $hash.png";
	exec($command);

	// copy the file to the cache directory
	copy("$hash.png", $this->CACHE_DIR . "/$hash.png");

	chdir($current_dir);

	}

function transform($text) {

  preg_match_all("/\[tex\](.*?)\[\/tex\]/si", $text, $matches);

  for ($i = 0; $i < count($matches[0]); $i++) {

    $position = strpos($text, $matches[0][$i]);
    $thunk = $matches[1][$i];

    $hash = md5($thunk);
    $full_name = $this->CACHE_DIR . "/" .
                 $hash . ".png";
    $url = $this->URL_PATH . "/" .
           $hash . ".png";

    if (!is_file($full_name)) {
      $this->render_latex($thunk, $hash);
      $this->cleanup($hash);
    }

    $text = substr_replace($text,
      "<img src=\"$url\" alt=\"Formula: $i\" />",
      $position, strlen($matches[0][$i]));
  }

  return $text;
}

}
