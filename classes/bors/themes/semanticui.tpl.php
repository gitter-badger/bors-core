<!doctype html>
<html class="no-js">
<head>
	<meta charset="utf-8">
	<title><?= htmlspecialchars($self->browser_title()); ?></title>
	<meta name="description" content="">
	<meta name=viewport content="width=device-width, initial-scale=1">
	<meta name="mobile-web-app-capable" content="yes">

	<link rel="stylesheet" href="//cdn.jsdelivr.net/semantic-ui/0.19.3/css/semantic.min.css">
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700,300&subset=latin,vietnamese' rel='stylesheet' type='text/css'>

</head>
<body>

	<div class="ui page grid">
		<div class="computer tablet only row">
			<div class="ui inverted menu navbar">
				<a href="" class="brand item">Project Name</a>
				<a href="" class="active item">Link</a>
				<a href="" class="item">Link</a>
				<a href="" class="item">Link</a>
				<a class="ui dropdown item">Dropdown
				  <i class="dropdown icon"></i>
				  <div class="menu">
					<div class="item">Action</div>
					<div class="item">Another action</div>
					<div class="item">Something else here</div>
					<div class="ui divider"></div>
					<div class="item">Seperated link</div>
					<div class="item">One more seperated link</div>
				  </div>
				</a>
				<div class="right menu">
					<a href="" class="active item">Default</a>
					<a href="" class="item">Static top</a>
					<a href="" class="item">Fixed top</a>
				</div>
			</div>
		</div>
		<div class="mobile only narrow row">
			<div class="ui inverted navbar menu">
				<a href="" class="brand item">Project Name</a>
				<div class="right menu open">
					<a href="" class="menu item">
						<i class="reorder icon"></i>
					</a>
				</div>
			</div>
			<div class="ui vertical navbar menu">
				<a href="" class="active item">Link</a>
				<a href="" class="item">Link</a>
				<a href="" class="item">Link</a>
				<div class="ui item">
					<div class="text">Dropdown</div>
					<div class="menu">
						<a class="item">Action</a>
						<a class="item">Another action</a>
						<a class="item">Something else here</a>
						<a class="ui aider"></a>
						<a class="item">Seperated link</a>
						<a class="item">One more seperated link</a>
					  </div>
				</div>
				<div class="menu">
					<a href="" class="active item">Default</a>
					<a href="" class="item">Static top</a>
					<a href="" class="item">Fixed top</a>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="column padding-reset">
<?php require __DIR__.'/semanticui/breadcrumbs.tpl.php'; ?>
				<div class="ui large message">
					<h1 class="ui huge header"><?= htmlspecialchars($self->page_title()); ?></h1>
					<p>This example is a quick exercise to illustrate how the default, static navbar and fixed to top navbar work. It includes the responsive CSS and HTML, so it also adapts to your viewport and device.</p>
					<a href="" class="ui blue button">View navbar docs &raquo;</a>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="column">
<?= $self->body(); ?>
			</div>
		</div>

	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/semantic-ui/0.13.0/javascript/semantic.min.js"></script>
</body>
</html>
