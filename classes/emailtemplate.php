<?php

class PeepSoEmailTemplate
{
	// default tokens
	private $aTokens = array(
		'date' => '',
		'datetime' => '',
		'email_contents' => '',

		'fromemail' => '',
		'fromfullname' => '',
		'fromfirstname' => '',
		'fromlogin' => '',

		'permalink' => '',
		'sitename' => '',
		'siteurl' => '',
		'unsubscribeurl' => '',

		'useremail' => '',
		'userfullname' => '',
		'userfirstname' => '',
		'userlogin' => '',
		
		'currentuserfullname' => '',

		'activityurl' => '',
		'profileurl' => '',

		'year' => '',
		'digestemailcontent' => ''
	);
	
	private $aRawToken = array(
		'digestemailcontent'
	);


	/*
	 * Constructor
	 */
	public function __construct()
	{
		$this->init_tokens();
	}


	/*
	 * Initializes the token contents with values that will not change from one email to the next
	 */
	public function init_tokens()
	{
		$dt_format = get_option('date_format');
		$tm_format = get_option('time_format');

		$this->aTokens['date'] = date($dt_format);
		$this->aTokens['datetime'] = date($dt_format . ' ' . $tm_format);
		$this->aTokens['sitename'] = get_bloginfo('name');
		$this->aTokens['siteurl'] = get_bloginfo('wpurl');
		$this->aTokens['unsubscribeurl'] = PeepSo::get_page('profile') . '?pref';
		$this->aTokens['year'] = date('Y');

		$this->aTokens['activityurl'] = PeepSo::get_page('activity');
		$this->aTokens['profileurl'] = PeepSo::get_page('profile');
	}


	/*
	 * Sets a token's value
	 * @param string $name The name of the token
	 * @param string $value The value to be used for this token
	 */
	public function set_token($name, $value)
	{
		if ('post_content' == $name)
			$value = substr(trip_tags($value), 0, 30) . '...';
		$this->aTokens[$name] = $value;
	}

	/**
	 * Sets a token's value based on a given $data
	 * @param array $data An array of tokens
	 */
	public function set_tokens($data)
	{
		foreach ($data as $name => $value) {
			$this->aTokens[$name] = $value;
		}
	}


	/*
	 * Replaces tokens found in the email template and message contents
	 * @param string $template The email template contents
	 * @param string $content The message content to be inserted into the template
	 * @return string The new message contents with the all tokens found within the email content replace
	 */
	public function replace_tokens($template, $content)
	{
		$content = $this->replace_content_tokens($content);
		$content = wpautop($content, TRUE);

		$result = str_replace('{email_contents}', $content, $template);
		$result = $this->replace_content_tokens($result, FALSE);
		return ($result);
	}

	/**
	 * Searches through a string and replaces the tokens with corresponding values
	 * @param  string $content The string to replace the contents of
	 * @return string The string with the tokens replaced
	 */
	public function replace_content_tokens($content, $linkify = TRUE)
	{
		// look for any other tokens and replace their values
		foreach ($this->aTokens as $token => $value) {
			$replace_token = '{' . $token . '}';
			if (TRUE === $linkify && 'http' == strtolower( substr( $value, 0, 4 ) ) ) {
				$tag_pos = strpos($content, $replace_token);
				if ($tag_pos !== false) {
					if ($tag_pos > 6 && ("href" == substr($content, $tag_pos-6,4))) {
						$content = str_ireplace($replace_token, $value, $content);
					} else {
						$content = str_ireplace($replace_token, '<a href="' . $value . '">' . $value . '</a>', $content);
					}
				}
			} else if (in_array($token, $this->aRawToken)) {
				$content = str_ireplace($replace_token, $value, $content);
			} else {
				$content = str_ireplace($replace_token, htmlspecialchars($value), $content);
			}
		}

		return ($content);
	}
}

// EOF
