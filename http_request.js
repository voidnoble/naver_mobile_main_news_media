'use strict';

const http = require('http');

http.get('http://m.naver.com', (res) => {
    let body = '';

    res.on('data', (chunk) => {
        body += chunk;
    });

    res.on('end', () => {
        console.log(body);
    });
}).on('error', (e) => {
    console.log(`Error ${e.message}`);
});
