<?php

function lcml_restore_format($txt)
{
	require_once BORS_CORE.'/engines/lcml/funcs.php';
	return restore_format($txt);
}
