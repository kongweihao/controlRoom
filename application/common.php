<?php

/**
 * 上一个请求的url
 * @return string
 */
function prevUrl(){
	return $_SERVER['HTTP_REFERER'];
}