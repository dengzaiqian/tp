let itemList = document.getElementsByClassName("item-list-border");
for(var i=0;i<itemList.length;i++){
	itemList[i].onclick = function(){
		console.log(i)
		window.location.href = 'merchantDistribution.html'
	}
}