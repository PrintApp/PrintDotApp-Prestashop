(function(){
    function fixPSOrders() {
		if (!window.jQuery) {
			setTimeout(fixPSOrders, 500);
			return;
		}
		const customizations = window.jQuery('#order-view-page .order-product-customization td > p');
		if (customizations.length == 0) {
		    setTimeout(fixPSOrders, 500);
			return;
		}
	    window.jQuery('#order-view-page .order-product-customization td > p').each((k,item) => {
            if (item.innerText.toLowerCase().match('print.app')) {
                const data = JSON.parse(
                    decodeURIComponent(
                       item.innerText.replace('Print.App :', '').trim()
                    )
                );
                
                const projectId = data.designId;
                
                const links = `<br/>
                    <a target="_blank" href="https://pdf.print.app/${projectId}">Download PDF</a><br/>
                    <a target="_blank"href="https://png.print.app/${projectId}">Download PNG</a><br/>
                    <a target="_blank" href="https://jpg.print.app/${projectId}">Download JPEG</a><br/>
                    <a target="_blank" href="https://admin.print.app/projects#${projectId}">Modify Project</a><br/>`;
    				  
                const previews = data.previews.map(prev => `<img src="${prev.url}" width="70px">`).join('');
                
                item.innerHTML = `<strong>Print.App: </strong> ${links}${previews}`;
            }
        });
	}
    fixPSOrders();
    
})();