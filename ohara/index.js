const qFrame = document.getElementById("questionFrame");
const aFrame = document.getElementById("answerFrame");
const titleEl = document.getElementById("currentTitle");
const titleStatSpan = document.getElementById("titleQuizStat");
const historyContainer = document.getElementById("historyContainer");
const offcanvasEl = document.getElementById("offcanvasMenu");
let offcanvasInstance;

let clickedOnce = false;
let timerInterval = null;
let elapsedSeconds = 0;

document.addEventListener("DOMContentLoaded", () => {
    offcanvasInstance = new bootstrap.Offcanvas(offcanvasEl);

    fetch("data.json")
        .then(r => r.json())
        .then(data => buildMenu(data))
        .catch(err => {
            document.getElementById("questionList").innerHTML = `<div class="text-danger">エラー: ${err.message}</div>`;
        });
});

// 電卓ロジック
const calcDisplay = document.getElementById("calcDisplay");
function calcAppend(val) {
    if (calcDisplay.value === "0" && val !== ".") calcDisplay.value = val;
    else calcDisplay.value += val;
}
function calcClear() { calcDisplay.value = "0"; }
function calcBack() {
    calcDisplay.value = calcDisplay.value.slice(0, -1);
    if (calcDisplay.value === "") calcDisplay.value = "0";
}
function calcEqual() {
    try {
        const expr = calcDisplay.value.replace(/[^0-9+\-*/.]/g, "");
        calcDisplay.value = eval(expr);
    } catch (e) {
        calcDisplay.value = "Error";
    }
}

function buildMenu(data) {
    const list = document.getElementById("questionList");
    list.innerHTML = "";

    for (const [folder, items] of Object.entries(data)) {
        const section = document.createElement("div");
        section.className = "mb-3";

        const titleWrapper = document.createElement("div");
        titleWrapper.className = "folder-title d-flex justify-content-between align-items-center mb-2";
        titleWrapper.innerHTML = `<span>${folder}</span>`;

        let firstVideo = (items || []).find(f => typeof f === "string" && f.toLowerCase().endsWith(".mp4"));
        if (firstVideo) {
            const vBtn = document.createElement("button");
            vBtn.className = "btn btn-sm btn-outline-primary video-btn py-0";
            vBtn.dataset.video = "data/" + firstVideo;
            vBtn.textContent = "動画";
            vBtn.onclick = (e) => { e.stopPropagation(); showVideoModal("data/" + firstVideo); };
            titleWrapper.appendChild(vBtn);
        }
        section.appendChild(titleWrapper);

        const btnGroup = document.createElement("div");
        btnGroup.className = "d-grid gap-1";

        (items || []).forEach(item => {
            if (typeof item === "string" && !item.toLowerCase().endsWith(".mp4")) {
                const btn = document.createElement("button");
                btn.className = "btn btn-light btn-sm text-start border";
                btn.dataset.file = "data/" + item;

                // ボタン内の構造: テキスト
                const spanText = document.createElement("span");
                spanText.textContent = item.split("/").pop().replace(".html", "");
                btn.appendChild(spanText);

                // ブックマークアイコン
                const icon = document.createElement("i");
                icon.className = "fav-icon bi bi-bookmark-fill";
                icon.style.display = "none"; // 初期は非表示
                btn.appendChild(icon);

                btn.onclick = () => {
                    // 選択状態の更新
                    document.querySelectorAll("#questionList .btn").forEach(b => b.classList.remove("selected-question"));
                    btn.classList.add("selected-question");

                    document.getElementById("welcomeMessage").classList.add("d-none");
                    qFrame.src = btn.dataset.file;
                    aFrame.classList.add("d-none");
                    qFrame.classList.remove("d-none");
                    titleEl.textContent = spanText.textContent;
                    clickedOnce = false;

                    if (window.innerWidth < 992) {
                        offcanvasInstance.hide();
                    }
                };
                btnGroup.appendChild(btn);
            }
        });

        section.appendChild(btnGroup);
        list.appendChild(section);
    }
    updateMenuHighlight();
    updateVideoButtons();
    updateMenuBookmarks();
}

function loadVideoHistory() { return JSON.parse(localStorage.getItem("video_history") || "{}"); }
function saveVideoHistory(p) {
    const d = loadVideoHistory();
    if (!d[p]) d[p] = { watched: true, count: 0, lastWatched: "" };
    d[p].watched = true; d[p].count++; d[p].lastWatched = new Date().toLocaleString("ja-JP");
    localStorage.setItem("video_history", JSON.stringify(d));
}
function updateVideoButtons() {
    const h = loadVideoHistory();
    document.querySelectorAll(".video-btn").forEach(b => {
        if (h[b.dataset.video]?.watched) { b.classList.remove("btn-outline-primary"); b.classList.add("btn-primary"); }
    });
}

function showVideoModal(src) {
    saveVideoHistory(src);
    updateVideoButtons();
    let m = document.getElementById("videoModal");
    m.querySelector("video").src = src;
    new bootstrap.Modal(m).show();

    if (!m.dataset.listenerAdded) {
        m.addEventListener("hidden.bs.modal", () => {
            const v = m.querySelector("video");
            v.pause();
            v.src = "";
        });
        m.dataset.listenerAdded = "true";
    }
}

function loadResults() { return JSON.parse(localStorage.getItem("quiz_results") || "{}"); }
function saveResult(file, correct, wrong, timeSec) {
    const d = loadResults();
    if (!d[file]) d[file] = [];
    d[file].unshift({ correct, wrong, rate: Math.round((correct / (correct + wrong || 1)) * 100), time: timeSec, date: new Date().toLocaleString("ja-JP") });
    if (d[file].length > 10) d[file] = d[file].slice(0, 10);
    localStorage.setItem("quiz_results", JSON.stringify(d));
    updateMenuHighlight();
}

function updateMenuHighlight() {
    const d = loadResults();
    document.querySelectorAll("#questionList button[data-file]").forEach(b => {
        const f = b.dataset.file.split("/").pop();
        const r = d[f] || [];
        if (r.some(x => x.rate === 100)) b.style.color = "blue";
        else if (r[0]?.wrong > 0) b.style.color = "red";
        else b.style.color = "";
    });
}

qFrame.addEventListener("load", () => {
    const doc = qFrame.contentDocument; if (!doc) return;
    const pn = doc.querySelector(".page-number"); if (pn) pn.style.display = "none";
    const btn = doc.querySelector("#btn_Answer, #btn_Answer_Text"); if (!btn) return;

    hideLabels(doc); startTimer(doc);

    // input_commaクラスを持つinputタグに3桁カンマ区切り機能を追加
    doc.querySelectorAll("input.input_comma").forEach(input => {
        input.addEventListener("input", (e) => {
            let val = e.target.value.replace(/,/g, "").replace(/[^0-9]/g, "");
            e.target.value = val ? new Intl.NumberFormat('en-US').format(val) : "";
        });
    });
    btn.textContent = "回答"; clickedOnce = false;

    const qFile = qFrame.src.split("/").pop();
    const stats = getQuizStats(qFile);
    titleStatSpan.textContent = stats.count > 0 ? `回答: ${stats.count}回 / 平均: ${stats.avgRate}%` : "未回答";

    const bookmarkBtn = doc.getElementById("btnElearningBookmark");
    if (bookmarkBtn) {
        const bms = loadBookmarks();
        bookmarkBtn.style.backgroundColor = bms.includes(qFile) ? "yellow" : "";
        bookmarkBtn.onclick = () => {
            let cur = loadBookmarks();
            if (cur.includes(qFile)) { cur = cur.filter(x => x !== qFile); bookmarkBtn.style.backgroundColor = ""; }
            else { cur.push(qFile); bookmarkBtn.style.backgroundColor = "yellow"; }
            saveBookmarks(cur);
            updateMenuBookmarks(); // メニューの★表示を更新
        };
    }

    btn.onclick = async () => {
        const aFile = qFrame.src.replace(".html", "A.html");
        if (!clickedOnce) {
            stopTimer();
            try {
                const res = await fetch(aFile); const html = await res.text();
                const ansDoc = new DOMParser().parseFromString(html, "text/html");
                let c = 0, w = 0;
                doc.querySelectorAll("input[id],select[id]").forEach(el => {
                    const cel = ansDoc.querySelector(`#${el.id}`); if (!cel) return;
                    const v = (el.value || "").replace(/,/g, "").trim(), target = (cel.value || "").replace(/,/g, "").trim();
                    if (v === "" && target === "") return;
                    if (v === target) { el.style.backgroundColor = "rgba(0,255,0,0.2)"; c++; }
                    else { el.style.backgroundColor = "rgba(255,0,0,0.2)"; w++; }
                });
                const rContent = document.getElementById("resultContent");
                rContent.innerHTML = `<b class="text-success">正解:${c}</b> / <b class="text-danger">不正解:${w}</b><br><small>時間:${formatTime(elapsedSeconds)}</small>`;
                new bootstrap.Modal(document.getElementById("resultModal")).show();
                saveResult(qFile, c, w, elapsedSeconds);
                btn.textContent = "解説"; clickedOnce = true;
            } catch (e) { alert("正解取得失敗: " + e); }
        } else {
            aFrame.src = aFile; aFrame.onload = () => hideLabels(aFrame.contentDocument, true);
            qFrame.classList.add("d-none"); aFrame.classList.remove("d-none");
        }
    };
});

function hideLabels(doc, isA = false) {
    ["p.cancel#btn_Cancel", "div.result"].forEach(s => { const x = doc.querySelector(s); if (x) x.style.display = "none"; });
    if (isA) { const a = doc.querySelector("h2#btn_Answer"); if (a) a.style.display = "none"; }
}

function startTimer(doc) {
    const t = doc.getElementById("v_time_all"); if (!t) return;
    stopTimer(); elapsedSeconds = 0;
    const f = () => t.textContent = formatTime(elapsedSeconds);
    f(); timerInterval = setInterval(() => { elapsedSeconds++; f(); }, 1000);
}
function stopTimer() { clearInterval(timerInterval); timerInterval = null; }
function formatTime(s) { return `${Math.floor(s / 60)}分${String(s % 60).padStart(2, "0")}秒`; }

function loadBookmarks() { return JSON.parse(localStorage.getItem("quiz_bookmarks") || "[]"); }
function saveBookmarks(b) { localStorage.setItem("quiz_bookmarks", JSON.stringify(b)); }
function updateMenuBookmarks() {
    // Store bookmarks as a Set of decoded filenames for reliable matching
    const bms = new Set(loadBookmarks().map(b => decodeURIComponent(b)));

    document.querySelectorAll("#questionList button[data-file]").forEach(b => {
        const fStr = b.dataset.file.split("/").pop();
        const fDecoded = decodeURIComponent(fStr);

        const icon = b.querySelector(".fav-icon");
        if (icon) {
            icon.style.display = bms.has(fDecoded) ? "inline-block" : "none";
        }
    });
}
function getQuizStats(f) {
    const d = loadResults()[f] || [];
    if (!d.length) return { count: 0, avgRate: 0 };
    return { count: d.length, avgRate: Math.round(d.reduce((s, r) => s + r.rate, 0) / d.length) };
}

document.getElementById("historyModal").addEventListener("show.bs.modal", () => {
    const d = loadResults();
    let h = '<table class="table table-sm table-condensed" style="font-size:0.75rem"><thead><tr><th>問題</th><th>率</th><th>時間</th><th>日</th></tr></thead><tbody>';
    for (const [f, recs] of Object.entries(d)) {
        recs.forEach(r => { h += `<tr><td>${f.replace('.html', '')}</td><td>${r.rate}%</td><td>${formatTime(r.time)}</td><td>${r.date.split(' ')[0]}</td></tr>`; });
    }
    document.getElementById("historyContainer").innerHTML = h + '</tbody></table>';
});
document.getElementById("videoHistoryModal").addEventListener("show.bs.modal", () => {
    const h = loadVideoHistory();
    let row = "";
    for (let t in h) { row += `<tr><td>${t}</td><td>${h[t].count}</td><td>${h[t].lastWatched.split(' ')[0]}</td></tr>`; }
    document.getElementById("video-history-body").innerHTML = row;
});

document.getElementById("downloadQuizBtn").onclick = () => downloadJSON("quiz_results.json", loadResults());
document.getElementById("downloadBookmarkBtn").onclick = () => downloadJSON("quiz_bookmarks.json", loadBookmarks());
document.getElementById("downloadVideoBtn").onclick = () => downloadJSON("video_history.json", loadVideoHistory());
function downloadJSON(n, d) {
    const b = new Blob([JSON.stringify(d, null, 2)], { type: "application/json" });
    const a = document.createElement("a"); a.href = URL.createObjectURL(b); a.download = n; a.click();
}
document.getElementById("uploadQuizBtn").onclick = () => document.getElementById("uploadQuizFile").click();
document.getElementById("uploadBookmarkBtn").onclick = () => document.getElementById("uploadBookmarkFile").click();
document.getElementById("uploadVideoBtn").onclick = () => document.getElementById("uploadVideoFile").click();
document.getElementById("uploadQuizFile").onchange = (e) => handleUpload(e.target, "quiz_results", updateMenuHighlight);
document.getElementById("uploadBookmarkFile").onchange = (e) => handleUpload(e.target, "quiz_bookmarks", updateMenuBookmarks);
document.getElementById("uploadVideoFile").onchange = (e) => handleUpload(e.target, "video_history", updateVideoButtons);
async function handleUpload(el, k, cb) {
    const f = el.files[0]; if (!f) return;
    const j = JSON.parse(await f.text()); localStorage.setItem(k, JSON.stringify(j));
    if (cb) cb(); alert("完了"); el.value = "";
}

document.getElementById("reloadBtn").onclick = () => location.reload();
document.getElementById("clearBtn").onclick = () => {
    const doc = qFrame.contentDocument; if (!doc) return;
    doc.querySelectorAll("input, select").forEach(el => { el.value = ""; el.style.backgroundColor = ""; });
};
// 仕訳機能ロジック
const journalAccounts = {
    "資産 (Assets)": [
        "現金", "普通預金", "当座預金", "売掛金", "クレジット売掛金", "受取手形", "電子記録債権", "未収入金", "未収利息", "貸付金", "手形貸付金", "仮払金", "立替金", "前払金", "貯蔵品", "受取商品券", "前払家賃", "前払保険料", "仮払消費税", "仮払法人税等", "貸倒引当金",
        "土地", "建物", "備品", "差入保証金", "減価償却累計額"
    ],
    "負債 (Liabilities)": [
        "買掛金", "支払手形", "未払金", "借入金", "手形借入金", "当座借越", "前受金", "預り金", "仮受金", "前受家賃", "前受地代", "前受手数料", "未払利息", "未払給料", "未払配当金", "未払消費税", "未払法人税等", "仮受消費税"
    ],
    "純資産 (Net Assets)": [
        "資本金", "利益準備金", "繰越利益剰余金"
    ],
    "費用 (Expenses)": [
        "仕入", "給料", "法定福利費", "支払家賃", "支払地代", "水道光熱費", "修繕費", "減価償却費", "消耗品費", "発送費", "旅費交通費", "通信費", "広告宣伝費", "支払手数料", "支払保険料", "租税公課", "雑費", "貸倒引当金繰入",
        "支払利息", "貸倒損失", "固定資産売却損", "雑損", "法人税、住民税及び事業税"
    ],
    "収益 (Revenue)": [
        "売上高", "売上", "受取手数料", "受取利息", "受取地代", "固定資産売却益", "償却債権取立益", "雑益"
    ]
};

function initJournal() {
    const container = document.getElementById("journalRowsContainer");
    container.innerHTML = "";

    // Create options HTML once
    let optionsHtml = '<option value=""></option>';
    for (const [category, items] of Object.entries(journalAccounts)) {
        optionsHtml += `<optgroup label="${category}">`;
        items.forEach(item => {
            optionsHtml += `<option value="${item}">${item}</option>`;
        });
        optionsHtml += `</optgroup>`;
    }

    // Generate 10 rows
    for (let i = 0; i < 10; i++) {
        const row = document.createElement("div");
        row.className = "row g-0"; // Removed border-bottom from row to handle it in cells if needed, or keep it.
        // Let's use individual cell borders to ensure a grid look.
        // We'll wrap the whole thing in a border container, so we just need internal borders.

        row.innerHTML = `
            <div class="col-4 p-2 border-end border-bottom">
                <input type="text" class="form-control form-control-sm journal-account" placeholder="" style="background-color: #fff;">
            </div>
            <div class="col-2 p-2 border-end border-bottom">
                <input type="text" class="form-control form-control-sm text-end journal-amount" placeholder="" inputmode="numeric" style="background-color: #fff;">
            </div>
            <div class="col-4 p-2 border-end border-bottom">
                <input type="text" class="form-control form-control-sm journal-account" placeholder="" style="background-color: #fff;">
            </div>
            <div class="col-2 p-2 border-bottom">
                <input type="text" class="form-control form-control-sm text-end journal-amount" placeholder="" inputmode="numeric" style="background-color: #fff;">
            </div>
        `;
        container.appendChild(row);
    }

    // Attach event listeners for amount formatting
    document.querySelectorAll(".journal-amount").forEach(input => {
        input.addEventListener("input", (e) => {
            let val = e.target.value.replace(/,/g, "").replace(/[^0-9]/g, "");
            e.target.value = val ? new Intl.NumberFormat('en-US').format(val) : "";
        });
    });
}

function clearJournal() {
    document.querySelectorAll(".journal-account").forEach(el => el.value = "");
    document.querySelectorAll(".journal-amount").forEach(el => el.value = "");
}

initJournal();
