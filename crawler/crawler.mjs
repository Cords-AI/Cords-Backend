import puppeteer from "puppeteer";

const url = process.argv[2];

(async function () {
    const browser = await puppeteer.launch({
        args: ['--no-sandbox'],
        executablePath: '/usr/bin/chromium'
    });
    const page = await browser.newPage();
    try {
        await page.goto(url);
    }
    catch (e) {
        console.log(JSON.stringify({ error: true }))
        browser.close();
        return;
    }

    const data = {};

    /** titles **/
    data.title = await page.evaluate(() => {
        return document.querySelector("title")?.innerHTML.trim();
    });

    data.ogTitle = await page.evaluate(() => {
        return document.querySelector("[property='og:title']")?.content.trim();
    });

    /** descriptions **/
    data.description = await page.evaluate(() => {
        return document.querySelector("meta[name='description']")?.content.trim();
    });

    data.ogDescription = await page.evaluate(() => {
        return document.querySelector("[property='og:description']")?.content.trim();
    });

    /** images **/
    data.shortcutIcon = await page.evaluate(() => {
        return document.querySelector("link[rel*='icon']")?.href.trim();
    });

    data.touchIcon = await page.evaluate(() => {
        return document.querySelector("[property='apple-touch-icon']")?.href.trim();
    });

    data.ogImage = await page.evaluate(() => {
        return document.querySelector("[property='og:image']")?.content.trim();
    });

    data.ogImageWidth = await page.evaluate(() => {
        return document.querySelector("[property='og:image:width']")?.content.trim();
    });

    data.ogImageHeight = await page.evaluate(() => {
        return document.querySelector("[property='og:image:height']")?.content.trim();
    });

    data.ogImageAlt = await page.evaluate(() => {
        return document.querySelector("[property='og:image:alt']")?.content.trim();
    });

    data.ogImageType = await page.evaluate(() => {
        return document.querySelector("[property='og:image:type']")?.content.trim();
    });

    browser.close();

    console.log(JSON.stringify(data))
})();
