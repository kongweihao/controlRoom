/**
 * js工具包
 */

/**
 * js获取url参数
 * @param string variable 参数名称
 * @return mixed 返回参数名称对应的值
 */
function getQueryVariable(variable){
	var query = window.location.search.substring(1);
	var vars = query.split("&");
	for (var i=0;i<vars.length;i++) {
		var pair = vars[i].split("=");
		if(pair[0] == variable){
			return pair[1];
		}
	}
	return(false);
}

/**
 * js获取url全部参数
 * @return string 返回所有参数和值组成的字符串
 */
function getAllQueryVariableWithout(arr){
	if(arr != ''){
		var query = window.location.search.substring(1);
		var vars = query.split("&");
		var str = '';
		for (var i=0;i<vars.length;i++) {
			var pair = vars[i].split("=");
			
			if(!arr.contains(pair[0])){
				if(pair[0] !=''){
					if(i == 0){
						str += '?'+vars[i];
					}else{
						str += '&'+vars[i];
					}			
				}
			}
		}
		if(str != ''){
			return str + '&';
		}else{
			return str + '?';
		}
	}
}

/**
 * 判断数组中是否包含指定的元素
 * 使用方法：arr.contains(['指定的元素'])
 */
Array.prototype.contains = function ( needle ) {
  for (i in this) {
    if (this[i] == needle) return true;
  }
  return false;
}