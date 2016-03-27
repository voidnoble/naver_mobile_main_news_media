/**
* 네이버 모바일웹 메인 제목들에 언론사,필자 추가
*
* author: cezips@gmail.com
* date: 2016-03-13
*
* 네이버 URL parameta:
*   oid= 언론사아이디
*   aid= 글아이디
*/
let tabIds = ['ENT', 'SPORTS', 'CARGAME'];

for(let i = 0; i < tabIds.length; i++) {
    fetchPanel(tabIds[i]);
}

/**
* Get the value of Querystring variable
* https://css-tricks.com/snippets/javascript/get-url-variables/
*
* Example URL:
*   http://www.example.com/index.php?id=1&image=awesome.jpg
*   Calling getQueryVariable("id") - would return "1".
*   Calling getQueryVariable("image") - would return "awesome.jpg".
*/
function getQueryVariable(query, variable)
{
    let vars = query.split("&");

    for (let i=0;i<vars.length;i++) {
       let pair = vars[i].split("=");
       if(pair[0] == variable){ return pair[1]; }
    }

    return(false);
}

function fetchPanel(tabId)
{
    let endpoint = `/include/grid/panel_${tabId}.shtml`;

    fetch(endpoint)
        .then(function(res) {
            return res.text();
        })
        .then(function(resText) {
            //console.log(resText);
            let doc = document.createElement('body');
            doc.innerHTML = resText;

            let leftItem = doc.querySelectorAll(`[data-section="${tabId}"] .ut_item`);
            let rightItem = doc.querySelectorAll('.utl_item');

            for(let i = 0; i < leftItem.length; i++) {
                let item = leftItem[i];
                let anchor = item.querySelector('a');
                let href = anchor.getAttribute('href');
                if (!href) {
                    continue;
                }

                if (/m\.entertain\.naver\.com|m\.news\.naver\.com/i.test(href)) {
                    // 글 direct url 이면 글 내용 fetch
                    let aid = getQueryVariable(href, "aid");
                    if (/\d+#.+/i.test(aid)) {
                        aid = /(\d+)#.+/i.exec(aid);
                    }

                    if (aid) {
                        //console.info('fetch article', href);
                        href = 'fetch.php?endpoint='+ encodeURIComponent(href);
                        fetch(href).then(function(res){
                            return res.text();
                        }).then(function(resText) {
                            let detailDoc = document.createElement('body');
                            detailDoc.innerHTML = resText;

                            let content = '',
                                author = '';

                            // 언론사명
                            // oid 값 데이터 테이블을 만들고 비교하여 만드는것도 다른 방법
                            let pressLogoImg = detailDoc.querySelector('.press_logo > img');
                            if (typeof pressLogoImg == 'undefined' || !pressLogoImg) return;

                            let pressName = pressLogoImg.getAttribute('alt');
                            if (typeof pressName == 'undefined' || !pressName) pressName = '';

                            // 기사입력, 최종수정
                            let regDate = detailDoc.querySelector('.author');
                            // 기사 내용
                            content = detailDoc.querySelector('.newsct_body').innerText;
                            if (content) {
                                // 필자
                                author = /(\[.+ 기자\])/i.exec(content);
                                if (!author) {
                                    author = /([0-9a-zA-Z][_0-9a-zA-Z-]*@[_0-9a-zA-Z-]+(\.[_0-9a-zA-Z-]+){1,2}$)/i.exec(content);
                                }

                                // 필자정보 못찾겠으면 공백 할당
                                if (!author) {
                                    author = '';
                                } else {
                                    author = ' / '+ author;
                                }
                            }

                            // 파싱한 자료들로 새 HTML 요소를 만들고
                            let itemDescElement = document.createElement('div');
                            itemDescElement.setAttribute('style', 'font-size:.9em;color:#4e88cf;line-height:1.7rem');
                            itemDescElement.innerHTML = pressName + author + regDate.outerHTML;
                            // 현 항목 끝에 추가
                            item.appendChild(itemDescElement);

                            console.log(doc);
                        }).catch(function(err){
                            console.error(err);
                        });

                        continue;
                    }
                }
            }

            let panelContainer = document.querySelector('.flick-panel');
            panelContainer.innerHTML += doc.innerHTML;
        })
        .catch(function(err) { console.error(err); });
}
