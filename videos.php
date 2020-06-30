<?php

// https://pt.stackoverflow.com/questions/170599/vantagens-e-desvantagens-de-usar-php-v%C3%ADdeo-stream
// http://www.tuxxin.com/php-mp4-streaming/
// https://blog.weckx.net/streaming-video-php/
// https://github.com/tuxxin/MP4Streaming/blob/master/streamer.php
// https://codesamplez.com/programming/php-html5-video-streaming-tutorial
// https://gist.github.com/ranacseruet/9826293

	if( session_status() !== PHP_SESSION_ACTIVE ) session_start();

	if( !isset( $_SESSION[ 'user' ] ) ){
		header( "HTTP/1.0 401 Not authorized" );
		return;
	}

	$debug = [ 'session' => $_SESSION ];

	if( !isset( $_GET[ 'v' ] ) ){
		header( "HTTP/1.0 400 Invalid Request" );
		return;
	}

	$debug[ 'v' ] = $_GET[ 'v' ];

	$file = __DIR__ .'/videos/video-'. str_pad( $_GET[ 'v' ], 2, '0', STR_PAD_LEFT ) .'.mp4';

	$debug[ 'file' ] = $file;


	#echo "<pre>";
	#var_dump( $debug );
	#echo "<pre>";


	// https://www.php.net/manual/pt_BR/function.readfile.php
	function smartReadFile( $location, $filename, $mimeType='application/octet-stream' ){

		if( !file_exists( $location ) ){
			header( "HTTP/1.0 404 Not Found" );
			return;
		}

		$size = filesize( $location );
		$time = date( 'r', filemtime( $location ) );

		$fm = @fopen( $location, 'rb' );
		if( !$fm ){
			header( "HTTP/1.0 505 Internal server error" );
			return;
		}

		$begin = 0;
		$end = $size;

		if( isset( $_SERVER[ 'HTTP_RANGE' ] ) ){
			if( preg_match( '/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER[ 'HTTP_RANGE' ], $matches ) ){
				$begin = intval( $matches[ 0 ] );
				if( !empty( $matches[ 1 ] ) )
					$end = intval( $matches[ 1 ] );
			}
		}

		if( $mime = mime_content_type( $location ) ){
			$mimeType = $mime;
		}

		header( ( $begin > 0 && $end < $size ) ? 'HTTP/1.0 206 Partial Content' : 'HTTP/1.0 200 OK' );
		header( 'Content-Type: '. $mimeType );
		header( 'Cache-Control: public, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Accept-Ranges: bytes' );
		header( 'Content-Length:'. ( $end - $begin ) );
		header( 'Content-Range: bytes '. $begin .'-'. $end .'/'. $size );
		header( 'Content-Disposition: inline; filename='. $filename );
		header( "Content-Transfer-Encoding: binary\n" );
		header( 'Last-Modified: '. $time );
		// header( 'Connection: close' );

		$cur = $begin;
		fseek( $fm, $begin, 0 );

		while( !feof( $fm ) && $cur < $end && ( connection_status() == 0 ) ){
			print fread( $fm, min( 1024 * 16, $end - $cur ) );
			$cur += 1024*16;
		}
	}

	smartReadFile( $file, basename( $file ) );
