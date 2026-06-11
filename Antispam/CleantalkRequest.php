<?php

/**
 * Request class
 *
 * @psalm-suppress PossiblyUnusedProperty
 */
class CleantalkRequest
{
     /**
     *  All http request headers
     * @var string
     */
     public $all_headers;

     /**
     *  Last error number
     * @var integer
     */
     public $last_error_no;

     /**
     *  Last error time
     * @var integer
     */
     public $last_error_time;

     /**
     *  Last error text
     * @var string
     */
     public $last_error_text;

    /**
     * User message
     * @var string|null
     */
    public $message;

    /**
     * Post example with last comments
     * @var string|null
     */
    public $example;

    /**
     * Auth key
     * @var string
     */
    public $auth_key;

    /**
     * Engine
     * @var string
     */
    public $agent;

    /**
     * Is check for stoplist,
     * valid are 0|1
     * @var int
     */
    public $stoplist_check;

    /**
     * Language server response,
     * valid are 'en' or 'ru'
     * @var string
     */
    public $response_lang;

    /**
     * User IP
     * @var string
     */
    public $sender_ip;

    /**
     * User email
     * @var string|null
     */
    public $sender_email;

    /**
     * User nickname
     * @var string
     */
    public $sender_nickname;

    /**
     * Sender info JSON string
     * @var string
     */
    public $sender_info;

    /**
     * Post info JSON string
     * @var string
     */
    public $post_info;

    /**
     * Is allow links, email and icq,
     * valid are 1|0
     * @var int
     */
    public $allow_links;

    /**
     * Time form filling
     * @var int
     */
    public $submit_time;

    public $x_forwarded_for = '';
    public $x_real_ip = '';

    /**
     * Is enable Java Script,
     * valid are 0|1|2
     * Status:
     *  null - JS html code not inserted into phpBB templates
     *  0 - JS disabled at the client browser
     *  1 - JS enabled at the client broswer
     * @var int|null
     */
    public $js_on;

    /**
     * user time zone
     * @var string
     */
    public $tz;

    /**
     * Feedback string,
     * valid are 'requset_id:(1|0)'
     * @var string
     */
    public $feedback;

    /**
     * Phone number
     * @var string|null
     */
    public $phone;

    /**
    * Method name
    * @var string
    */
    public $method_name = 'check_message';
}
