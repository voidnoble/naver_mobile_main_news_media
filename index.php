<?php namespace voidnoble;
include "vendor/autoload.php";
/**
* @brief 네이버 모바일 메인 크롤링
* @author voidnoble <cezips@gmail.com>
* @date 2016-03-20
* @description
* Simple DOM parser = http://simplehtmldom.sourceforge.net/manual.htm
*/

use Curl\Curl;
use Sunra\PhpSimple\HtmlDomParser;

$naverDomain = "http://m.naver.com/";
$panelFilePath = "include/grid/";

// 네이버 모바일 메인
$endpoint = "http://m.naver.com";

$html = getHtml($endpoint);

$dom = HtmlDomParser::str_get_html($html);

$head = $dom->find('head', 0);
$head->innertext = str_replace('document.domain = "naver.com";', 'document.domain = "'. $_SERVER["SERVER_NAME"] .'";', $head->innertext);

$body = $dom->find('body', 0);

// 메인 뉴스 분석
$tabId = "news";
$items = $body->find('.wrp.id_{$tabId} .ut_item');
parseItems($items);

// jQuery 삽입
$userJS = '<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-2.2.1.min.js"></script>';
// lazy-load 이미지들 그냥 로딩하도록
$userJS .= '<script>
    $(function(){
        $("#mflick").attr("style", "");
        //$("img").each(function(){ if ($(this).data("src")) $(this).attr("src", $(this).data("src")); });
        $(".fade").css("opacity", "1");
    });
</script>';

// Append a element
$body->innertext = $body->innertext . $userJS;

echo($dom);


// 패널 id들
$tabIds = ['ENT', 'SPORTS', 'CARGAME'];
// 패널들을 루프돌며 패널 출력
for($i = 0; $i < count($tabIds); $i++) {
    $tabId = $tabIds[$i];
    printPanel($tabId, $naverDomain, $panelFilePath);
}


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

function getPanel($naverDomain, $panelFilePath, $panelFileName)
{
    $endpoint = $naverDomain . $panelFilePath . $panelFileName;
    $html = getHtml($endpoint);

    return $html;
}

function savePanel($naverDomain, $panelFilePath, $panelFileName)
{
    $html = getPanel($naverDomain, $panelFilePath, $panelFileName);
    $fp = fopen($panelFilePath . $panelFileName, "w");
    fwrite($fp, $html);
    fclose($fp);
}

function printPanel($tabId, $naverDomain, $panelFilePath)
{
    echo("<h2>{$tabId}</h2>");

    $panelFileName = "panel_{$tabId}.shtml";
    $response = getPanel($naverDomain, $panelFilePath, $panelFileName);
    $doc = HtmlDomParser::str_get_html($response);

    $tabId = strtolower($tabId);

    if ($tabId == "cargame") {
        // uce_item, uct_item
        $items = $doc->find('.wrp.id_{$tabId} [class$="_item"]');
    } else {
        $items = $doc->find('.wrp.id_{$tabId} .ut_item');
        $rightItems = $doc->find('.utl_item');
    }

    parseItems($items);

    // lazy-load img to direct load
    $doc->outertext = str_replace('img data-src', 'img src', $doc->outertext);

    echo($doc->outertext);
}

/**
* 뉴스 링크 항목들 파싱
* Array $items
*/
function parseItems(&$items)
{
    //$result = [];

    foreach($items as $item) {
        $oid = "";
        $aid = "";

        $anchor = $item->find('a', 0);
        $href = $anchor->href;

        // anchor attribute data-gdid="{$tabId}_{$contentType}_{$oid}_{$aid}"
        $dataGDID = $anchor->{'data-gdid'};
        // 영역 아이디 = 탭아이디들과 같음
        $dataArea = $anchor->{'data-area'};
        // data-gdid attribute 있으면 뉴스 링크
        if ($dataGDID) {
            $dataGDIDs = explode('_', $dataGDID);
            // data-area^="NEWS_"
            if ($dataArea == "NEWS") {
                if (isset($dataGDIDs[2])) $oid = $dataGDIDs[2];
                if (isset($dataGDIDs[3])) $aid = $dataGDIDs[3];

                // NEWS area Detail url ex) http://m.news.naver.com/read.nhn?sid1=101&oid=008&aid=0003654311&mode=LSD
                $href = "http://m.news.naver.com/read.nhn?mode=LSD&oid={$oid}&aid={$aid}";
            }
            // data-area^="ENT_"
            elseif ($dataArea == "ENT") {
                // data-area^="ENT_NEWS_"
                if ($dataGDIDs[1] == "NEWS") {
                    if (isset($dataGDIDs[2])) $oid = $dataGDIDs[2];
                    if (isset($dataGDIDs[3])) $aid = $dataGDIDs[3];

                    // ENT area Detail url ex) http://m.entertain.naver.com/read?oid=311&aid=0000591120
                // data-area^="ENT_TVCAST_"
                } elseif ($dataGDIDs[1] == "TVCAST") {
                    //
                }
            }
            // data-area^="SPORTS_"
            elseif ($dataArea == "SPORTS") {
                if ($dataGDIDs[1] == "NEWS") {
                    if (isset($dataGDIDs[2])) $oid = $dataGDIDs[2];
                    if (isset($dataGDIDs[3])) $aid = $dataGDIDs[3];

                    // SPORTS area Detail url ex) http://m.sports.naver.com/kbaseball/news/read.nhn?oid=111&aid=0000451400
                } elseif ($dataGDIDs[1] == "VIDEO") {
                    //
                }
            }
            // data-area^="CARGAME_"
            elseif ($dataArea == "CARGAME") {
                // 이 경우는 href 파싱하여 사용
                // 매거진, 포스트 등 다양한데 네이버측에서 적절하게 구분되는 data attribute 사용안함
            }

            // 글번호가 없다면
            // parameta 중 oid 와 aid 만 있으면 Detail page response 됨
            if (!$oid || !$aid) {
                if (!$href) continue;   // 이것마저 없다면 Skip

                if ($dataArea == "CARGAME") {
                    // href 에 auto.naver.com 포함된 경우 Detail page 파싱
                    // ex) http://m.auto.naver.com/magazine/view.nhn?type=Theme&seq=17282
                    // ex) http://auto.naver.com/magazine/magazineThemeRead.nhn?isMobile=y&type=Theme&seq=17191
                    $aid = true;
                } else {
                    // 글 direct url 이면 글 내용 fetch
                    parse_str($href, $queryStrings);
                    if (!is_array($queryStrings)) continue;
                    $aid = (isset($queryStrings["aid"]))? $queryStrings["aid"] : false;
                }
            }

            // 뉴스 글번호 존재 = 직접 링크 = 링크 fetch = Detail page
            if ($aid) {
                $itemDesc = parseDetail($href);

                if ($itemDesc === false) continue;

                // 현 항목 끝에 추가
                $item->innertext = $item->innertext . $itemDesc;
                //$result[] = '{"href": "'. $href .'", "desc": "'. $itemDesc .'"}';
            }
        }
    }

    //return json_encode($result);
}

function parseDetail($href)
{
    $response = getHtml($href);

    $detailDoc = HtmlDomParser::str_get_html($response);

    if (!$detailDoc) return false;

    $itemDesc = "";

    // 탭아이디 CARGAME 중 자동차의 경우 .author_info 2개에 각각 언론사,글쓴이 정보가 있다
    if (strpos($href, 'auto.naver.com') !== false) {
        $authorInfos = $detailDoc->find('.article_info');
        if (is_array($authorInfos)) {
            foreach ($authorInfos as $authorInfo) {
                $itemDesc .= $authorInfo->outertext;
            }
        }
        else {
            $authorInfos = $detailDoc->find('.author_info');
            foreach ($authorInfos as $authorInfo) {
                $itemDesc .= $authorInfo->outertext;
            }
        }

        // CARGAME Detail 페이지가 euc-kr 로 되어 있는듯... UTF-8 로 변환
        $itemDesc = iconv('euc-kr', 'utf-8', $itemDesc);
        // 반환하고 끝냄
        return $itemDesc;
    }
    // 네이버포스트의 경우
    elseif (strpos($href, 'post.naver.com') !== false) {
        // 필자명
        //$author = $detailDoc->find('.se_container', 0);
        $author = $detailDoc->find('.se_author', 0);
        if (is_object($author)) {
            $itemDesc = '<p class="uct_origin"><span class="uct_s">'. $author->innertext .'</span></p>';
        }

        // CARGAME Detail 페이지가 euc-kr 로 되어 있는듯... UTF-8 로 변환
        //$itemDesc = iconv('euc-kr', 'utf-8', $itemDesc);

        // 반환하고 끝냄
        return $itemDesc;
    }

    // 언론사명
    // oid 값 데이터 테이블을 만들고 비교하여 만드는것도 다른 방법
    $pressLogoImg = $detailDoc->find('.press_logo > img', 0);
    if (!$pressLogoImg) return false;

    $pressName = $pressLogoImg->alt;
    if (!$pressName) $pressName = '';

    // 기사입력, 최종수정
    $regDate = $detailDoc->find('.author', 0);
    // 기사 내용
    $content = $detailDoc->find('.newsct_body', 0)->innertext;
    if ($content) {
        // 필자
        $author = "";

        preg_match("/\[.+ 기자\]/i", $content, $matches);
        if (is_array($matches) && count($matches) > 0) {
            $author = $matches[0];
        }

        if (!$author) {
            preg_match("/([0-9a-zA-Z][_0-9a-zA-Z-]*@[_0-9a-zA-Z-]+(\.[_0-9a-zA-Z-]+){1,2}$)/i", $content, $matches);
            if (is_array($matches) && count($matches) > 0) {
                $author = $matches[0];
            }
        }

        if ($author) {
            $author = ' / '. $author;
        }
    }

    // 파싱한 자료들로 새 HTML 요소를 만들고
    $itemDesc = '<div style="font-size:.9em;color:#4e88cf;line-height:1.7rem">'. $pressName . $author . $regDate->outertext .'</div>';

    return $itemDesc;
}
