<?php

require_once('fabpot-Twig-2009-11-18-36416cf/lib/Twig/Autoloader.php');
require_once('fabpot-Twig-2009-11-18-36416cf/lib/Twig/ExtensionInterface.php');
require_once('fabpot-Twig-2009-11-18-36416cf/lib/Twig/Extension.php');
require_once('fabpot-Twig-2009-11-18-36416cf/lib/Twig/TokenParser.php');
require_once('fabpot-Twig-2009-11-18-36416cf/lib/Twig/Node.php');

class bors_templates_twig extends bors_templates_abstract
{
	function render()
	{
		Twig_Autoloader::register();

		$template_file = $this->full_path();
		if(!$template_file)
			return ec("Ошибка: не найден файл шаблона");

		$cache_dir = config('cache_dir').'/twig/';
		mkpath($cache_dir);

		$paths = array(dirname($template_file));
		foreach(bors_dirs() as $dir)
			if(is_dir($dir = "$dir/templates/"))
				$paths[] = $dir;

		$loader = new Twig_Loader_Filesystem($paths);
		$twig = new Twig_Environment($loader, array(
			'cache' => $cache_dir,
			'auto_reload' => true,
		));

		$twig->addExtension(new bors_twig_extension());

		$template = $twig->loadTemplate(basename($template_file));
		$result = $template->render($this->data);

		return $result;
	}
}

class bors_twig_extension extends Twig_Extension
{
	function getFilters()
	{
		return array(
			'lcml_bbh' => array('lcml_bbh', false),
		);
	}
	function getName() { return 'project'; }
	function getTokenParsers() { return array(new bors_twig_parser_module()); }
}

class bors_twig_parser_module extends Twig_TokenParser
{
	public function getTag() { return 'module'; }

	function parse(Twig_Token $token)
	{
		$lineno = $token->getLine();

		$params = array();
		do
		{
    		$next = $this->parser->getStream()->next()->getValue();
			if($next)
			{
				$operator = $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE)->getValue();
//				$value = $this->parser->getExpressionParser()->parseExpression();
			    $expr = $this->parser->getExpressionParser()->parseExpression();
//			    list(, $values) = $this->parser->getExpressionParser()->parseMultitargetExpression();
				$params[$next] = $expr;
			}
		} while($next);

//		var_dump($params);
//		echo "???";

	    return new bors_twig_node_module($token->getLine(), $params);
	}

}

class bors_twig_node_module extends Twig_Node
{
  protected $params;

  public function __construct($lineno, $params)
  {
    parent::__construct($lineno);
    $this->params = $params;
  }

  public function __toString() { return get_class($this); }

	public function compile($compiler)
	{
//		var_dump($this->params);

		$class_name = $this->params['class'];
		$object_id  = $this->params['id'];
		unset($this->params['class']);
		unset($this->params['id']);

/*		foreach($this->params as $key => $expr)
		{
		    $compiler
				->addDebugInfo($this)
				->write("\$obj = object_load();")
			;
		}
*/
	    $compiler
			->addDebugInfo($this)
			->write("echo object_load(")
			->subcompile($class_name)
			->write(",")
			->subcompile($object_id)
			->write(")->body();")
			;
	}
}