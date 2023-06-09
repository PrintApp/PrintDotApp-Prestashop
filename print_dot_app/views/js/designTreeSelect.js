/* global print_dot_app_current_design fetch wp_ajax_url*/
(function() {
    let attempts = 50;
    async function startTree() {
        if (!attempts) return;
        const main = document.querySelector('select[name="print_dot_app_design_select"]');
        if (!main) {
            attempts--;
            setTimeout(startTree,500);
            return;
        }
        console.log('tree started after attempts', attempts)
        
        const req   = new XMLHttpRequest();
        const pdaLoadItems = (path) => {
            return new Promise( async (res, rej) => {
                const data = new FormData();
                
                if (path) data.append('path', path);
                data.append('action', 'print_dot_app_fetch_designs');
                
                req.onreadystatechange = function() {
                    if (req.readyState == 4) {
                        if (req.status == 200) 
                            res(JSON.parse(req.responseText));
                        else
                            rej(req.responseText);
                    }
                };
                req.open('post', window.pda_ajax_url);
                req.send(data);
            });
        };
        
        const style = `
        <style id="print_dot_app_design_styling" type="text/css">
            div.print_dot_app_design_list {
                position: relative;
                float: left;
                width: 300px;
                height: 36px;
            }
            @media only screen and (max-width: 1280px) {
                div.print_dot_app_design_list {
                    width: 81.6%;
                }
            }
            div.print_dot_app_design_list > div {
                position: absolute;
                background: white;
                width: calc(100% - 22px);
                border: 1px solid #9052ab;
                padding: 10px;
                border-radius: 5px;
                z-index: 10;
            }
            div.print_dot_app_design_list input[type=radio] {
                margin-bottom: 3px;
            }
            .chevron {
                margin-right: 5px;
                cursor: pointer;
            }
            .chevron::before {
                border-style: solid;
                border-width: 0.25em 0.25em 0 0;
                content: '';
                display: inline-block;
                height: 0.45em;
                left: 0.15em;
                position: relative;
                top: 0.15em;
                transform: rotate(-45deg);
                vertical-align: top;
                width: 0.45em;
            }
    
            .chevron.right:before {
                margin-top: 6.5px;
                left: 0;
                transform: rotate(45deg);
            }
    
            .chevron.bottom:before {
                margin-top: 6.5px;
                top: 0;
                transform: rotate(135deg);
            }
    
            .chevron.left:before {
                left: 0.25em;
                transform: rotate(-135deg);
            }
            .folder > input[type="radio"] {
                margin-left: 20px;
            }
            div.item {
                cursor: pointer;
                padding: 4px;
                border-radius: 5px;
            }
            div.item:hover {
                background-color: #e5e1e1;
            }
            div.print_dot_app_design_list .level-1 {
                margin-left: 20px
            }
            div.print_dot_app_select {
                border: 1px solid #9052ab;
                border-radius: 5px;
                cursor:pointer;
                display: flex;
                float: left;
                justify-content: space-between;
                padding: 5px;
                width: 300px;
                margin-bottom: 15px;
            }
            @media only screen and (max-width: 1280px) {
                div.print_dot_app_select {
                    width: 300px;
                }
            }
            .print_dot_app_indent_list {
                margin-left: 20px
            }
            print_dot_app_design_list > div {
                max-height: 400px;
                overflow-y: scroll;
            }
        </style>`;
        document.head.insertAdjacentHTML('beforeEnd', style);
    
        // INITIATE ROOT ITEMS
        
        let input = await pdaLoadItems();
    
        const sel = document.createElement('div');
        sel.classList.add("print_dot_app_design_list");
    
        let html = `<div>
                        <div class="item">
                            <input type="radio" value="0" name="print_dot_app_design_select"/>
                            <span class="folder" data-id="0">None</span>
                        </div>`;
    
        if (input && input.data && input.data.items) {
            input.data.items.forEach(item=>{
                html+= `<div>
                            <div class="item">
                                <input type="radio" value="${item.id}__${item.title}" name="print_dot_app_design_select"/>
                                ${item.type == 'folder' ? '<span class="chevron right" data-id="'+item.id+'"></span>' : ''}
                                <span>${item.title}</span>
                            </div>
                        </div>`;
            });
        }
        html+='</div>';
        
        sel.innerHTML = html;
        sel.style.display = 'none';
    
        main.style.display='none';
        const cDesignVals = print_dot_app_current_design.split('__');
        main.insertAdjacentHTML('beforebegin', `<div class="print_dot_app_select">
            <span>${cDesignVals[1] || 'None'}</span>
            <span class="chevron bottom"></span>
        </div>`);
        
        const newMain = main.previousElementSibling;
        
        main.insertAdjacentElement('beforebegin',sel);
        
        // WHEN DROPDOWN IS CLICKED, SHOW THE LIST
        document.querySelector('.print_dot_app_select').addEventListener('click', function(e) {
            newMain.style.display = 'none';
            sel.style.display = 'block';
            e.stopPropagation();
        });
        
        // UPON CLICK AWAY, CLOSE THE DROP DOWN AND SET THE SELECTED VALUE
        window.addEventListener('click', function() {
            const selectedValue = document.querySelector('[name=print_dot_app_design_select]:checked');
            if (selectedValue) {
                const selectedValItems = selectedValue.value.split('__');
                newMain.children[0].innerText = selectedValItems[2] || selectedValItems[1] || 'None';
            }
            newMain.style.display = 'flex';
            sel.style.display = 'none';
        });
        
        // ONLY WHEN CLICK ON DROPDOWN DON'T HIDE ANYTHING
        document.querySelector('div.print_dot_app_design_list').addEventListener('click', e => e.stopPropagation());
        
        // ADD EVENT LISTENERS TO ROOT ITEMS
        const chevStatus = {};
    
        async function chevClicked (event) {
            const target = event.target;
            if (target.dataset && target.dataset.id && target.dataset.id.match('folder')) {
                // CHECK IF ITEMS LOADED ALREADY, ONLY OPEN AND CLOSE IF SO
                if (chevStatus.hasOwnProperty(target.dataset.id)) {
                    if (chevStatus[target.dataset.id].isOpen) {
                        chevStatus[target.dataset.id].list.close();
                        target.classList.remove('bottom');
                        target.classList.add('right');
                        chevStatus[target.dataset.id].isOpen = false;
                    }else{
                        chevStatus[target.dataset.id].list.open();
                        target.classList.remove('right');
                        target.classList.add('bottom');
                        chevStatus[target.dataset.id].isOpen = true;
                    }
                    return;
                }
    
                target.classList.remove('right');
                target.classList.add('bottom');
    
                const loader =  '<span class="spinner is-active"></span>';
                target.parentElement.insertAdjacentHTML('beforeend',loader);
    
                input = await pdaLoadItems(target.dataset.id);
    
                target.parentElement.lastChild.remove();
    
                if (input && input.data) {
                    let list = `<div class="print_dot_app_indent_list">`;
                    input.data.forEach(item=>{
                        list += `<div class="item">
                                    <input type="radio" value="${item.id}__${item.title}" name="print_dot_app_design_select"/>
                                    ${item.items && item.items.length ? '<span class="chevron right" data-id="'+item.id+'"></span>' : ''}
                                    <span">${item.title}</span>
                                </div>`;
                    });
                    list += '</div>';
                    target.parentElement.parentElement.insertAdjacentHTML('beforeend',list);
                    
                    chevStatus[target.dataset.id] = {};
                    chevStatus[target.dataset.id].list = target.parentElement.parentElement.lastChild;
                    chevStatus[target.dataset.id].list.open = function() { this.style.display = 'block' };
                    chevStatus[target.dataset.id].list.close = function() { this.style.display = 'none' };
                    chevStatus[target.dataset.id].isOpen = true;
                    
                    // LISTEN TO NEW CHEVRON CLICKS
                    for (let item of target.parentElement.parentElement.lastChild.children) {
                        const chevron = item.children[1];
                        chevron.addEventListener('click', chevClicked);
                    }
                }
            }
            event.stopPropagation();
        }
        document.querySelectorAll('.print_dot_app_design_list .chevron').forEach(item => {
            item.addEventListener('click', chevClicked);
        });
        // REMOVE OLD INPUT ELEMENT
        main.remove();
    }
    startTree();
})();
