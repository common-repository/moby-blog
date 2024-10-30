function blockCat(){

	var allowed_cats = document.getElementById("allowed_cats");
	var disallowed_cats = document.getElementById("disallowed_cats");
	var res_blocked_cats = document.getElementById("res_blocked_cats");
	var options = allowed_cats.options;
	var i = options.length;
	while (i--) {
		var current = options[i];
		if (current.selected) {
			//var option = document.createElement("option");
			disallowed_cats.add(current);
		}
	}

	var options2 = disallowed_cats.options;
	var j = options2.length;
	var blkCats = [];
	while (j--) {
		var current2 = options2[j];
		blkCats.push(current2.value);
	}
	//alert(blkCats);
	res_blocked_cats.value = blkCats;
}

function unblockCat(){
	var disallowed_cats = document.getElementById("disallowed_cats");
	var allowed_cats = document.getElementById("allowed_cats");
	var res_blocked_cats = document.getElementById("res_blocked_cats");
	var options = disallowed_cats.options;
	var i = options.length;
	while (i--) {
		var current = options[i];
		if (current.selected) {
			//var option = document.createElement("option");
			allowed_cats.add(current);
		}
	}

	var options2 = disallowed_cats.options;
	var j = options2.length;
	var blkCats = [];
	while (j--) {
		var current2 = options2[j];
		blkCats.push(current2.value);
	}

	res_blocked_cats.value = blkCats;

}