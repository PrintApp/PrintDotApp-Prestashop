/* global pa_admin_values api_key and product_id */

(async function() {
    if (typeof pa_admin_values === 'undefined') return;
    
    const padLoadData = () => {
        return new Promise( async (resolve, reject) => {
            const   request   = new XMLHttpRequest();
            
            request.onreadystatechange = function() {
                if (request.readyState == 4) {
                    if (request.status == 200) 
                        resolve(JSON.parse(request.responseText));
                    else
                        reject(request.responseText);
                }
            };
            request.open('GET', `https://run.print.app/${pa_admin_values.api_key}/${pa_admin_values.product_id}/ps/admin`);
            request.send();
        });
    },
    element = document.getElementById('print_app_tab'),
    setLoading = () => {
        element.innerHTML = `<div class="print-app-loading" style="width:4rem;height:4rem;background-repeat:no-repeat;background-image:url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MHB4IiBoZWlnaHQ9IjUwcHgiIHZpZXdCb3g9IjAgMCA1MCA1MCIgPg0KPGNpcmNsZSBmaWxsPSJub25lIiBvcGFjaXR5PSIwLjEiIHN0cm9rZT0iIzAwMDAwMCIgc3Ryb2tlLXdpZHRoPSI1IiBjeD0iMjUiIGN5PSIyNSIgcj0iMjAiLz4NCjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDI1LDI1KSByb3RhdGUoLTkwKSI+DQogICAgIDxjaXJjbGUgIHN0eWxlPSJzdHJva2U6IzQ4QjBGNzsgZmlsbDpub25lOyBzdHJva2Utd2lkdGg6IDVweDsgc3Ryb2tlLWxpbmVjYXA6IHJvdW5kIiBzdHJva2UtZGFzaGFycmF5PSIxMTAiIHN0cm9rZS1kYXNob2Zmc2V0PSIwIiAgY3g9IjAiIGN5PSIwIiByPSIyMCI+DQogICAgICAgICA8YW5pbWF0ZSBhdHRyaWJ1dGVOYW1lPSJzdHJva2UtZGFzaG9mZnNldCIgdmFsdWVzPSIzNjA7MTQwIiBkdXI9IjIuMnMiIGtleVRpbWVzPSIwOzEiIGNhbGNNb2RlPSJzcGxpbmUiIGZpbGw9ImZyZWV6ZSIga2V5U3BsaW5lcz0iMC40MSwwLjMxNCwwLjgsMC41NCIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiIGJlZ2luPSIwIi8+DQogICAgICAgICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InJvdGF0ZSIgdmFsdWVzPSIwOzI3NDszNjAiIGtleVRpbWVzPSIwOzAuNzQ7MSIgY2FsY01vZGU9ImxpbmVhciIgZHVyPSIyLjJzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIgYmVnaW49IjAiLz4NCiAgICAgICAgIDxhbmltYXRlIGF0dHJpYnV0ZU5hbWU9InN0cm9rZSIgdmFsdWVzPSIjZWY0NDQ0OyNmYWNjMTU7I2EzZTYzNTsjNDhCMEY3OyM2RDVDQUU7IzEwQ0ZCRDsjZmFjYzE1OyNlZjQ0NDQiIGZpbGw9ImZyZWV6ZSIgZHVyPSI4cyIgYmVnaW49IjAiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIi8+DQogICAgIDwvY2lyY2xlPg0KPC9nPg0KPC9zdmc+')"></div>`;
    }

    if (!element) return;
    setLoading();
    const designContent = await padLoadData();
    if (!designContent || !designContent.html) return element.innerHTML = '<div class="print-app-error">Error loading design</div>';
    
    let productTitle = encodeURIComponent(pa_admin_values.product_title || '');
    designContent.html = designContent.html.replace(/(href=")(.+?)(")/, `$1$2${productTitle}$3`);
    
    element.innerHTML = designContent.html;

})();
