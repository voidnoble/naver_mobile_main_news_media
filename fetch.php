<?php namespace voidnoble;
include "vendor/autoload.php";

use Curl\Curl;

$endpoint = $_GET['endpoint'];
echo getHtml($endpoint);

function getHtml($endpoint)
{
    $response = "";

    $curl = new Curl;
    // 응답 값을 브라우저에 표시하지 말고 값을 리턴
    $curl->setopt(CURLOPT_RETURNTRANSFER, TRUE);
    $curl->setopt(CURLOPT_SSL_VERIFYPEER, FALSE);
    // 브라우저처럼 보이기 위해 user agent 사용
    $curl->setUserAgent('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
    // $curl->setReferrer('');
    // $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
    $curl->get($endpoint);

    if ($curl->error) {
        $response = $curl->error_code;
    }
    else {
        $response = $curl->response;
    }

    $curl->close();

    return $response;
}
