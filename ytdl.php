<?php

/**
 * This webapp helps you download YouTube videos
 * Nothing new, but I wanted to research this stuff from scratch.
 * Sergei Sokolov, hello@sergeisokolov.com, for SnapClip. Trento, Italy, 2013.
 */

class YTDL {
	public $videoId;	// YouTube video ID
	public $videoData = array();	// general video infos
	public $links = array();		// array of var quality links

	/**
	 * Fetches info from a remote URL
	 */
	private function fetchInfo( $url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
	function setVideoId( $id='') {
		$field = 'video';
		
		if( empty($id)) {
			$in = filter_input( INPUT_GET, $field)?:filter_input( INPUT_POST, $field);
			// http://www.youtube.com/watch?v=27Ce--_qzFM&xxxxx
			// youtu.be27Ce--_qzFM
			if( empty($in)) return FALSE;
			
			if( preg_match( '#watch\?v=([^&]+)#', $in, $match)) {
				$id = $match[1];
			} elseif( preg_match( '#youtu.be/(.+)#', $in, $match)) {
				$id = $match[1];
			} else {
				return FALSE;
			}
		}
		$this->videoId = $id;
	}
	
	private function parseVideoData( $infoLine) {
		$videoData = array();
		parse_str( $infoLine, $videoData);
		if( count( $videoData) == 0) {
			echo "Empty data. Html follows:";
			echo $infoLine;
			return;
		} else {
			//print_r($video_data);
		}
		$this->videoData = $videoData;
		/* Keys of $this->videoData:
			allow_ratings, length_seconds, video_id, watermark, avg_rating, pltype, fmt_list, fexp, storyboard_spec, 
			status, sendtmp, ftoken, abd, plid, allow_embed, vq, c, video_verticals, iurlmaxres, account_playback_token, 
			idpj, iurl, share_icons, token, title, ldpj, eventid, has_cc, adaptive_fmts, dashmpd, 
			url_encoded_fmt_stream_map, ptk, view_count, track_embed, use_cipher_signature, iurlsd, keywords, 
			timestamp, hl, muted, thumbnail_url, endscreen_module, author, dash
		*/
	}
	
	private function parseFormats() {
		if( !isset( $this->videoData[ 'url_encoded_fmt_stream_map'])) {
			echo "Error: No fmt_stream_map.";
			return NULL;
		}
		
		$formatsArray = explode( ',', $this->videoData['url_encoded_fmt_stream_map']);
		foreach( $formatsArray AS $formatString) {
			$format = array();
			parse_str( $formatString, $format);
			/* Example:
				[sig] => 72C268F7B86365DBB49DF4F4DB2782962E5CC64F.CCCBFE67D7199276B7F7CCE792CCB8E97DC3E7F2
				[type] => video/webm; codecs="vp8.0, vorbis"
				[itag] => 45
				[quality] => hd720
				[fallback_host] => tc.v6.cache5.c.youtube.com
				[url] => http://.... (long url with many params)
			*/
			
			// Decode the long url
			$urlString = urldecode($format['url']);
			$break = strpos( $urlString, '?');
			$url = substr( $urlString, 0, $break);
			$params = substr( $urlString, $break+1);
	
			$urlData = array();
			parse_str( $params, $urlData);
			/* Example $urlData:
				[mt] => 1378753340
				[mv] => m
				[ipbits] => 8
				[ratebypass] => yes
				[sparams] => cp,id,ip,ipbits,itag,ratebypass,source,upn,expire
				[source] => youtube
				[ms] => au
				[fexp] => 923435,932100,932217,914090,916626,901476,929117,929121,929906,929907,929922,929127,929129,929131,929930,936403,925726,925720,925722,925718,925714,929917,906945,929933,920302,906842,913428,919811,913563,919373,930803,938701,931924,936308,909549,900816,912711,904494,904497,939903,900375,900382,934507,907231,936312,906001
				[expire] => 1378777057
				[sver] => 3
				[ip] => 77.72.198.21
				[key] => yt1
				[cp] => U0hWTVdSVV9KUUNONl9PTFlBOmU2dzRGc1NTV0ty
				[upn] => g2xLgK0BHu4
				[id] => 82ed6b50cb6f5b09
				[itag] => 45
			*/
			
			// Build dl url
			$urlData['downloadUrl'] = $urlString . "&signature=" . $format['sig'];
			
			// Add the array to links
			$this->links[] = array_merge( $format, $urlData);
		}
		return count( $this->links);
	}
	
	/**
	 * Ouputs an html list of links found
	 */
	function linksHtml() {
		$html = '';
		$tmpl = <<<EOFHTML
<h1>%s</h1>
<div>
	<a href="http://www.youtube.com/watch?v=%s" target="_blank"><img src="%s" border="0"></a>
</div>
<ul>
%s
</ul>
EOFHTML;
		foreach( $this->links AS $L) {
			$html .= sprintf(
				'<li><a href="/?action=download&mime=%s&url=%s&title=%s">%s</a> [%s]</li>' . PHP_EOL,
				base64_encode( $L['type']),
				base64_encode( $L['downloadUrl']),
				base64_encode( $this->videoData['title']),
				$L['quality'],
				$L['type']
			);
		}
		
		$html = sprintf( $tmpl,
			$this->videoData['title'],
			$this->videoData['video_id'],
			$this->videoData['iurl'],
			$html
		);
		return $html;
	}
	
	function pageHtml( $content = '') {
		$tmpl = file_get_contents('ytdl.html');
		return sprintf( $tmpl, $content);
	}
	
	function formHtml() {
		$tmpl = <<<HTML
<div class="form">
	<form action="/" method="post" name="getvideo">
		<label for="video">YouTube video code or URL:</label>
		<input id="video" type="text" name="video" maxlength="150" value="%s">
		<button type="submit">Search</button>
		<button type="reset">Cancel</button>
	</form>
</div>
HTML;
		return sprintf( $tmpl, $this->videoId);
	}
	
	function getInfo() {
		if( empty($this->videoId)) {
			return NULL;
		}
		$infoUrl = "http://www.youtube.com/get_video_info?&video_id=" . $this->videoId;
		$infoLine = $this->fetchInfo( $infoUrl);
		
		$this->parseVideoData( $infoLine); // fills 'videoData' property
		$n = $this->parseFormats();
		
		return $n;
	}	
	
	/**
	 * Checks if the call is a download attempt and processes it.
	 * Otherwise just returns FALSE
	 */
	function download() {
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING);
		if( $action !== 'download') return FALSE;
		
		$url = base64_decode( filter_input( INPUT_GET, 'url', FILTER_SANITIZE_STRING));
		$mime = base64_decode( filter_input( INPUT_GET, 'mime', FILTER_SANITIZE_STRING));
		$title = base64_decode( filter_input( INPUT_GET, 'title', FILTER_SANITIZE_STRING));
		
		$extension = str_replace( array('/', 'x-'), '', strstr( strstr($mime,';',TRUE)?:$mime, '/'));
				
		header('Content-Type: "' . $mime . '"');
		header('Content-Disposition: attachment; filename="' . urldecode($title.'.'.$extension) . '"');
		header("Content-Transfer-Encoding: binary");
		header('Expires: 0');
		header('Pragma: no-cache');
		readfile($url);
		exit();
	}


}