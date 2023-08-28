/*
library.js

Written by Wolf I. Butler.

This is a javascript function library used by some of the scripts within this system. It uses jQuery for AJAX support.

*/

$(document).ready( function(){
	$('#playing').load('playing.php');
	$('#status').load('status.php');
	$('#actions').load('actions.php');
	refresh_playing();
	refresh_status();
});

function refresh_playing() {
	setTimeout( function() {
	  $('#playing').load('playing.php');
	  refresh_playing();
	}, 5000);
}

function refresh_status() {
	setTimeout( function() {
	  $('#status').load('status.php');
	  refresh_status();
	}, 15000);
}