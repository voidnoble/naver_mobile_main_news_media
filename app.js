fetch('/include/grid/panel_ENT.shtml')
    .then(function(res) {
        return res.text();
    })
    .then(function(resText) {
        //console.log(resText);
        let doc = document.createElement('body');
        doc.innerHTML = resText;
        let blocks = document.querySelectorAll('.brick-vowel .grid1_inner');
        console.log(blocks);
    })
    .catch(function(err) { console.error(err); });
