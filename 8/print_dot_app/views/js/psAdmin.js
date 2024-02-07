
function domReady(callbackFunction){
    if(document.readyState != 'loading')
        callbackFunction(event)
    else
        document.addEventListener("DOMContentLoaded", callbackFunction)
}

domReady(function(event) {
    const elements = document.querySelectorAll('#order-view-page .order-product-customization td > p');
    console.log('elements', elements);
    elements.forEach(item => {
        if (item.innerText.includes('PrintApp_Customization')) {
            let str = item.innerText.split('PrintApp_Customization')[1];
            if (!str) return;
            const data = JSON.parse(decodeURIComponent( str.trim() ));

            const projectId = data.projectId;

            const links = `<ul>
                <li><a target="_blank" href="https://pdf.print.app/${projectId}">Download PDF</a></li>
                <li><a target="_blank" href="https://png.print.app/${projectId}">Download PNG</a></li>
                <li><a target="_blank" href="https://jpeg.print.app/${projectId}">Download JPEG</a></li>
                <li><a target="_blank" href="https://tiff.print.app/${projectId}">Download TIFF</a></li>
                <li><a target="_blank" href="https://admin.print.app/projects#${projectId}">Modify Project</a></li>
                </ul>`;

            const previews = data.previews.map(prev => `<img src="${prev.url}" width="80px">`).join('');

            item.innerHTML = `<strong>PrintApp Customization</strong> ${links}${previews}`;
        }
    });
});

