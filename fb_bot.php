<?php
/**
 * PHP Curl status update script
 * @since Sep 2010
 * @version 29.5.2012
 * @author Luka Pušić <luka@pusic.com>
 */
/*
 * Required parameters
 */
require_once 'simple_html_dom.php';

$email            = 'ganganimaulik';
$pass             = 'password';
$birthday_message = "Happy birthday :)";

/*
 * Optional parameters
 */
$uagent  = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';
$cookies = 'cookies.txt';
touch($cookies);
$device_name = 'Home'; #in case you have location checking turned on
$debug       = true;

function return_var_dump(/*...*/){
    $args=func_get_args();
    ob_start();
    call_user_func_array('var_dump',$args);
    return ob_get_clean();
}

/*
 * @return form input field names & values
 */
function parse_inputs($html)
{
    $dom = new DOMDocument;
    libxml_clear_errors();
    if(!@$dom->loadxml($html)){
        throw new RuntimeException('failed to parse xml. errors: '.return_var_dump(libxml_get_errors()));
    }
    $inputs = $dom->getElementsByTagName('input');
    return ($inputs);
}
/*
 * @return form action url
 */
function parse_action($html)
{
    $dom = new DOMDocument;
    libxml_clear_errors();
    if(!@$dom->loadxml($html)){
        throw new RuntimeException('failed to parse xml. errors: '.return_var_dump(libxml_get_errors()));
    }
    $form_action = $dom->getElementsByTagName('form')->item(0)->getAttribute('action');
    if (!strpos($form_action, "//")) {
        $form_action = "https://m.facebook.com$form_action";
    }
    return ($form_action);
}
function login()
{
    /*
     * Grab login page and parameters
     */
    $loginpage   = grab_home();
    $form_action = parse_action($loginpage);
    $inputs      = parse_inputs($loginpage);
    $post_params = "";
    foreach ($inputs as $input) {
        switch ($input->getAttribute('name')) {
            case 'email':
                $post_params .= 'email=' . urlencode($GLOBALS['email']) . '&';
                break;
            case 'pass':
                $post_params .= 'pass=' . urlencode($GLOBALS['pass']) . '&';
                break;
            default:
                $post_params .= $input->getAttribute('name') . '=' . urlencode($input->getAttribute('value')) . '&';
        }
    }
    echo "[i] Using these login parameters: $post_params";
    /*
     * Login using previously collected form parameters
     */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, $form_action);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
    $loggedin = curl_exec($ch);
    if ($GLOBALS['debug']) {
        echo $loggedin;
    }
    curl_close($ch);

}
/*
 * grab and return the homepage
 */
function grab_home()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, 'https://m.facebook.com/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $html = curl_exec($ch);
    if ($GLOBALS['debug']) {
        echo $html;
    }
    curl_close($ch);
    return ($html);
}
/*
 * grab and return the birthday page
 */
function grab_birthday()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, 'https://m.facebook.com/browse/birthdays/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $html = curl_exec($ch);
    if ($GLOBALS['debug']) {
        echo $html;
    }
    curl_close($ch);
    return ($html);
}
/*
 * update facebook status
 */
function update($msg)
{
    $html = grab_birthday();

    $html = str_get_html($html);

    $privacy_lock = 0;
    $success      = 0;

    //this loop will process each friend having birthday today.
    foreach ($html->find('#root .bi .bj') as $element) {
        $inputs = array();
        if ($element->find('form')[0]) {
            //echo $element->find('form')[0];
            foreach ($element->find('input') as $input) {
                //echo "\niiiii: ". ($input->name);
                $inputs[$input->name] = urlencode($input->value);
            }
            $inputs['message'] = urlencode($msg);
            $form_action       = 'http://m.facebook.com' . $element->find('form')[0]->action;
            print_r($inputs);
            echo $url;
            echo "\n\n";

            //url-ify the data for the POST
            $post_params = '';
            foreach ($inputs as $key => $value) {$post_params .= $key . '=' . $value . '&';}
            rtrim($post_params, '&');

            if ($GLOBALS['debug']) {
                echo "\nBirthday post form action: $form_action\n";
                echo "\nBirthday post params: $post_params\n";
            }

            /*
             * post the message
             */
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
            curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
            curl_setopt($ch, CURLOPT_URL, $form_action);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
            $updated = curl_exec($ch);
            if ($GLOBALS['debug']) {
                echo $updated;
            }
            curl_close($ch);
            $success++;
        } else {
            $privacy_lock++;
        }
    }
    echo "\n\n\n\n";
    echo $privacy_lock . " people have disabled wall-post.\n";
    echo $success . " friends have been wished.\n";
}
function logout()
{
    $dom = new DOMDocument;
    libxml_clear_errors();
    if(!@$dom->loadxml(grab_home())){
        throw new RuntimeException('failed to parse grab_home() xml. errors: '.return_var_dump(libxml_get_errors()));
    }
    $links = $dom->getElementsByTagName('a');
    foreach ($links as $link) {
        if (strpos($link->getAttribute('href'), 'logout.php')) {
            $logout = $link->getAttribute('href');
            break;
        }
    }
    $url = 'https://m.facebook.com' . $logout;
    /*
     * just logout lol
     */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $loggedout = curl_exec($ch);
    if ($GLOBALS['debug']) {
        echo "\nLogout url = $url\n";
        echo $loggedout;
    }
    curl_close($ch);
    echo "\n[i] Logged out.\n";
}
login();
update($birthday_message);
logout();
unlink($cookies);
