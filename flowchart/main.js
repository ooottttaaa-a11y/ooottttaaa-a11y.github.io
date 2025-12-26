document.addEventListener("DOMContentLoaded", function () {
    var rightcard = false;
    var tempblock;
    var tempblock2;
    document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span></div><div class="blocktext">                        <p class="blocktitle">New visitor</p><p class="blockdesc">Triggers when somebody visits a specified page</p>        </div></div></div>';
    flowy(document.getElementById("canvas"), drag, release, snapping);
    function addEventListenerMulti(type, listener, capture, selector) {
        var nodes = document.querySelectorAll(selector);
        for (var i = 0; i < nodes.length; i++) {
            nodes[i].addEventListener(type, listener, capture);
        }
    }
    function snapping(drag, first) {
        var grab = drag.querySelector(".grabme");
        grab.parentNode.removeChild(grab);
        var blockin = drag.querySelector(".blockin");
        blockin.parentNode.removeChild(blockin);
        if (drag.querySelector(".blockelemtype").value == "1") {
            drag.innerHTML += "<div class='blockyleft'><p class='blockyname'>New visitor</p></div><div class='blockydiv'></div><div class='blockyinfo'>When a <span>new visitor</span> goes to <span>Site 1</span></div>";
        } else if (drag.querySelector(".blockelemtype").value == "2") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/actionblue.svg'><p class='blockyname'>Action is performed</p></div><div class='blockydiv'></div><div class='blockyinfo'>When <span>Action 1</span> is performed</div>";
        } else if (drag.querySelector(".blockelemtype").value == "3") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/timeblue.svg'><p class='blockyname'>Time has passed</p></div><div class='blockydiv'></div><div class='blockyinfo'>When <span>10 seconds</span> have passed</div>";
        } else if (drag.querySelector(".blockelemtype").value == "4") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/errorblue.svg'><p class='blockyname'>Error prompt</p></div><div class='blockydiv'></div><div class='blockyinfo'>When <span>Error 1</span> is triggered</div>";
        } else if (drag.querySelector(".blockelemtype").value == "5") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/databaseorange.svg'><p class='blockyname'>New database entry</p></div><div class='blockydiv'></div><div class='blockyinfo'>Add <span>Data object</span> to <span>Database 1</span></div>";
        } else if (drag.querySelector(".blockelemtype").value == "6") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/databaseorange.svg'><p class='blockyname'>Update database</p></div><div class='blockydiv'></div><div class='blockyinfo'>Update <span>Database 1</span></div>";
        } else if (drag.querySelector(".blockelemtype").value == "7") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/actionorange.svg'><p class='blockyname'>Perform an action</p></div><div class='blockydiv'></div><div class='blockyinfo'>Perform <span>Action 1</span></div>";
        } else if (drag.querySelector(".blockelemtype").value == "8") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/twitterorange.svg'><p class='blockyname'>Make a tweet</p></div><div class='blockydiv'></div><div class='blockyinfo'>Tweet <span>Query 1</span> with the account <span>@alyssaxuu</span></div>";
        } else if (drag.querySelector(".blockelemtype").value == "9") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/logred.svg'><p class='blockyname'>Add new log entry</p></div><div class='blockydiv'></div><div class='blockyinfo'>Add new <span>success</span> log entry</div>";
        } else if (drag.querySelector(".blockelemtype").value == "10") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/logred.svg'><p class='blockyname'>Update logs</p></div><div class='blockydiv'></div><div class='blockyinfo'>Edit <span>Log Entry 1</span></div>";
        } else if (drag.querySelector(".blockelemtype").value == "11") {
            drag.innerHTML += "<div class='blockyleft'><img src='assets/errorred.svg'><p class='blockyname'>Prompt an error</p></div><div class='blockydiv'></div><div class='blockyinfo'>Trigger <span>Error 1</span></div>";
        }
        return true;
    }
    function drag(block) {
        block.classList.add("blockdisabled");
        tempblock2 = block;
    }
    function release() {
        if (tempblock2) {
            tempblock2.classList.remove("blockdisabled");
        }
    }


    document.getElementById("closecard").addEventListener("click", function () {
        document.getElementById("leftcard").classList.toggle("minimized");
        document.getElementById("canvas").classList.toggle("expanded");
    });

    function closeModal() {
        rightcard = false;
        document.getElementById("properties").classList.remove("expanded");
        document.getElementById("modal-backdrop").classList.remove("itson");
        setTimeout(function () {
            document.getElementById("propwrap").classList.remove("itson");
        }, 300);
        if (tempblock) {
            tempblock.classList.remove("selectedblock");
        }
    }

    document.getElementById("close").addEventListener("click", function () {
        closeModal();
    });

    document.getElementById("modal-backdrop").addEventListener("click", function () {
        closeModal();
    });

    document.getElementById("removeblock").addEventListener("click", function () {
        flowy.deleteBlocks();
        closeModal();
    });
    var aclick = false;
    var noinfo = false;
    var beginTouch = function (event) {
        aclick = true;
        noinfo = false;
        if (event.target.closest(".create-flowy")) {
            noinfo = true;
        }
    }
    var checkTouch = function (event) {
        aclick = false;
    }

    function openModal(block) {
        tempblock = block;
        rightcard = true;
        document.getElementById("propwrap").classList.add("itson");
        document.getElementById("modal-backdrop").classList.add("itson");
        setTimeout(function () {
            document.getElementById("properties").classList.add("expanded");
        }, 10);
        tempblock.classList.add("selectedblock");

        // Populate property inputs
        var title = tempblock.querySelector(".blockyname") ? tempblock.querySelector(".blockyname").innerText : "";
        document.getElementById("prop-title").value = title;

        // Populate list
        var list = document.getElementById("prop-list");
        list.innerHTML = "";
        var info = tempblock.querySelector(".blockyinfo");
        var existingItems = Array.from(info.querySelectorAll("li span, li a")).map(el => el.innerText);

        if (existingItems.length === 0 && info.innerText !== "No items added") {
            // fallback for plain text description
            if (info.innerText.trim()) {
                existingItems = [info.innerText.trim()];
            }
        }

        existingItems.forEach(text => {
            var li = document.createElement("li");
            li.innerHTML = `<span>${text}</span><span class="remove-item">×</span>`;
            li.querySelector(".remove-item").addEventListener("click", function () {
                li.remove();
                updateBlockCanvasList();
            });
            list.appendChild(li);
        });
    }

    // Double click listener for blocks on canvas
    document.addEventListener("dblclick", function (event) {
        var block = event.target.closest(".block");
        if (block && !block.classList.contains("dragging")) {
            // Re-select to ensure it's the current one in the DOM
            openModal(block);
        }
    });

    // Helper to get total number of blocks for unique ID generation
    function getNextBlockId() {
        var data = flowy.output();
        if (!data || !data.blockarr) return 0;
        var maxId = -1;
        data.blockarr.forEach(b => {
            if (b.id > maxId) maxId = b.id;
        });
        return maxId + 1;
    }

    var doneTouch = function (event) {
        // Single click logic removed as requested (double click for modal)
    }
    addEventListener("mousedown", beginTouch, false);
    addEventListener("mousemove", checkTouch, false);
    addEventListener("mouseup", doneTouch, false);
    addEventListenerMulti("touchstart", beginTouch, false, ".block");

    // Real-time updates for Title
    document.getElementById("prop-title").addEventListener("input", function () {
        if (tempblock && tempblock.querySelector(".blockyname")) {
            tempblock.querySelector(".blockyname").innerText = this.value;
        }
    });

    // List Item logic
    document.getElementById("add-item-btn").addEventListener("click", function () {
        var text = document.getElementById("new-item-text").value;
        if (text && tempblock) {
            addItemToList(text);
            document.getElementById("new-item-text").value = "";
        }
    });

    document.getElementById("new-item-text").addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            document.getElementById("add-item-btn").click();
        }
    });

    function addItemToList(text) {
        var list = document.getElementById("prop-list");
        var li = document.createElement("li");

        // Generate a unique identifier for this item to link it to the child block
        var itemId = "item-" + Date.now();
        li.setAttribute("data-item-id", itemId);
        li.innerHTML = `<span>${text}</span><span class="remove-item">×</span>`;

        // Remove item logic
        li.querySelector(".remove-item").addEventListener("click", function (e) {
            e.stopPropagation();
            li.remove();
            updateBlockCanvasList();
        });

        list.appendChild(li);
        updateBlockCanvasList();

        // Auto-add block logic
        autoAddChildBlock(itemId, text);
    }

    function updateBlockCanvasList() {
        if (!tempblock) return;
        var info = tempblock.querySelector(".blockyinfo");
        var items = Array.from(document.querySelectorAll("#prop-list li span:first-child")).map(s => s.innerText);

        if (items.length > 0) {
            var html = "<ul>";
            items.forEach((item, index) => {
                // Link with a dummy hash for now, we'll associate with actual IDs later if possible
                html += `<li><a href="#" class="jump-link" data-index="${index}">${item}</a></li>`;
            });
            html += "</ul>";
            info.innerHTML = html;
        } else {
            info.innerHTML = "No items added";
        }

        // Re-attach jump listeners
        attachJumpListeners(tempblock);
    }

    function attachJumpListeners(block) {
        block.querySelectorAll(".jump-link").forEach(link => {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                var index = this.getAttribute("data-index");
                jumpToChild(block, index);
            });
        });
    }

    function autoAddChildBlock(itemId, text) {
        if (!tempblock) return;

        var parentId = parseInt(tempblock.querySelector(".blockid").value);
        var output = flowy.output();

        // We simulate adding a child block by updating the flowy data
        // For simplicity, we'll use blocktype 2 (Action) as a default child
        var newId = getNextBlockId();

        var newBlock = {
            "id": newId,
            "parent": parentId,
            "data": [
                { "name": "blockid", "value": newId },
                { "name": "blockelemtype", "value": "2" }
            ],
            "attr": [
                { "class": "blockelem noselect block", "style": "" }
            ]
        };

        output.blockarr.push(newBlock);

        // We need to re-import to reflect changes
        // Warning: import might reset the view or lose some transient states
        flowy.import(output);

        // Re-open the modal for the parent to continue editing if needed
        // BUT, flowy.import recreates DOM, so we need to find the new parent element
        setTimeout(function () {
            var newParent = document.querySelector(`.blockid[value="${parentId}"]`).closest(".block");
            tempblock = newParent;
            tempblock.classList.add("selectedblock");

            // Map the itemId to the new childId in the DOM or a internal registry
            var childEl = document.querySelector(`.blockid[value="${newId}"]`).closest(".block");
            if (childEl) {
                childEl.setAttribute("data-owner-item", itemId);
            }
        }, 100);
    }

    function jumpToChild(parentBlock, index) {
        // Logic to find the child block connected to the parent
        // flowy doesn't give us the ID easily, but we can look at the arrows.
        var parentId = parentBlock.querySelector(".blockid").value;
        var arrows = flowy.output().arrowarr;
        var connections = arrows.filter(a => a.parent == parentId);

        if (connections[index]) {
            var childId = connections[index].child;
            var childElement = document.querySelector(`.blockid[value="${childId}"]`).closest(".block");
            if (childElement) {
                childElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                childElement.classList.add("highlight-flash");
                setTimeout(() => childElement.classList.remove("highlight-flash"), 1000);
            }
        }
    }

    // Persistence Logic
    document.getElementById("save-btn").addEventListener("click", function () {
        var data = flowy.output();
        localStorage.setItem("flowy_data", JSON.stringify(data));
        alert("Flowchart saved!");
    });

    document.getElementById("load-btn").addEventListener("click", function () {
        var savedData = localStorage.getItem("flowy_data");
        if (savedData) {
            flowy.import(JSON.parse(savedData));
        } else {
            alert("No saved data found.");
        }
    });

    document.getElementById("clear-btn").addEventListener("click", function () {
        if (confirm("Clear flowchart and saved data?")) {
            flowy.deleteBlocks();
            localStorage.removeItem("flowy_data");
        }
    });

    // Auto-load on startup
    var initialData = localStorage.getItem("flowy_data");
    if (initialData) {
        flowy.import(JSON.parse(initialData));
    }
});
