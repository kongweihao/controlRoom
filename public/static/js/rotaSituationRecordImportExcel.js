importScripts('../common/xlsx.core.min.js');
let workbook; // 以二进制流方式读取得到的整份excel表格对象
let keyForImport; // 保存导入excel的key
let keyForImportDouble; // keyForImport的替身
let errorReport = []; // 错误报告
let worksheet_rowJson = []; // 获取每张表的所有字段
const getExcelData = {} // 获取excel数据
let dateFormatReg; // 日期格式正则表达式
addEventListener('message', function (response) {
	const res = response
	switch(res.data.step){
		case 0:
			// step0数据初始化
			workbook = res.data.data.workbook
			keyForImport = res.data.data.keyForImport 
			keyForImportDouble = JSON.parse(JSON.stringify(keyForImport))
			postMessage({step: -1, msg: '正在检查 sheet 的数量、表名并提出不需要的表格'})
			postMessage({step: 1})
			break;
		case 1:
			// step1
			// 第一步：判断sheet是否符合条件
			errorReport = [] 
			for(let i = 0; i < keyForImportDouble.length ; i++){
				let is_key_exit = 0;
				for(let j = 0; j < workbook.SheetNames.length; j++){
					if(workbook.SheetNames[j] === keyForImportDouble[i].ctrr_rsrm_name){
						is_key_exit = 1;
						break
					}
				}
				if(is_key_exit === 0){
					let errorReport_item = keyForImportDouble[i]
					keyForImportDouble.splice(i,1)
					postMessage({step: -1, msg: '缺少表格:【' + errorReport_item.ctrr_rsrm_name + '】'})
					errorReport.push('缺少表格【' + errorReport_item.ctrr_rsrm_name + '】')
				}
			}
			// 告诉主线程可以进入下一步
			postMessage({step: 2, data:{
				errorReport: errorReport
			}})
			break;
		case 2:
			// step2
			// 第二步：获取需要的表格，相当于剔除workbook中不要的表格（同时处理将时间类型数据处理成规定格式yyyy/MM/dd HH:mm:ss）
			// 注意：在整理整张表格前，需要对导入表中的excel数据提前进行转化并进行时间格式检验，
			// 		但是本次的处理是将所有excel中设定为时间类型的字段进行处理，但是并不是所有时间类型的数据都是系统要求的时间字段
			//      因此真正的时间字段校验会放在与其他类型字段一起校验的那块逻辑中
			errorReport = []
			dateFormatReg = res.data.data.dateFormatReg
			keyForImportDouble.forEach(item => {
				if(item.length !== 0){
					for(const wbs_key in workbook.Sheets[item.ctrr_rsrm_name]){
						if(workbook.Sheets[item.ctrr_rsrm_name][wbs_key].t === 'd'){
							// 数据项 v或者w都有可能是所需的数据项，因此都要判断
							const ditem = workbook.Sheets[item.ctrr_rsrm_name][wbs_key].w
							if(ditem){
								if(dateFormatReg.test(ditem)){
									workbook.Sheets[item.ctrr_rsrm_name][wbs_key].v = ditem
								}else{
									// 这里其实会将表格中所有时间类型的字段全量的判断一遍，包括系统要求的时间字段，
									// 但是这里的错误信息只需全都默认为非系统默认的字段即可，
									// 因为如果系统是要求的时间字段，将会展示错误报告，而不是错误提示框，如果显示的只是错误提示框，说明系统要求字段已完全符合要求
									errorReport.push(item.ctrr_rsrm_name + '表格中的' + wbs_key + '的字段中值为：【' + ditem + '】，您设置了其他时间格式，不过该字段不是系统规定的时间字段导入不会做阻挠');
								}
							}
						}
					}
					if(errorReport.length >= 10 ){
						errorReport[9] = errorReport[9] + '...等共有' + errorReport.length + '条。'
						errorReport = errorReport.slice(0,9)
						postMessage({step: -1, msg: '时间格式存在较多问题：'})
					}
					const worksheet_to_json = XLSX.utils.sheet_to_json(workbook.Sheets[item.ctrr_rsrm_name], {
						raw: false, //使用原始值（true）或格式化的字符串（false）
					})
					if(worksheet_to_json.length !== 0){
						getExcelData[item.ctrr_rsrm_name] = worksheet_to_json
						// 获取每张表格的所有字段
						worksheet_rowJson[item.ctrr_rsrm_name] = XLSX.utils.sheet_to_json(workbook.Sheets[item.ctrr_rsrm_name], {
							/** Default value for null/undefined values */
							defval: '',//给defval赋值为空的字符串 用于获取每张表格的所有字段
						})[0]
					}else{
						postMessage({step: -1, msg: '空表：【' + item.ctrr_rsrm_name + '】'})
						errorReport.push('【' + item.ctrr_rsrm_name + '】：为空表')
					}
				}
			})
			// 告诉主线程可以进入下一步
			postMessage({step: 3, data: {
				errorReport: errorReport
			}})
			break;
		case 3:
			// step3
			// 第三步：判断 每个sheet中的字段是否符合条件，但不是对数据进行全量检测
			errorReport = []
			// 必填字段是否有值
			const neededFiledErrMsg = []
			postMessage({step: -1, msg: '正在检查必填字段'})
			
			for(let i = 0; i < keyForImportDouble.length; i++){
				let str = ''
				if(!worksheet_rowJson[keyForImportDouble[i].ctrr_rsrm_name]){
					// 如果表内数据为空则直接进入下一次循环
					break
				}
				for(let key in keyForImportDouble[i].children){
					const field = keyForImportDouble[i].children[key].ctrr_rsrm_name
					if(!worksheet_rowJson[keyForImportDouble[i].ctrr_rsrm_name].hasOwnProperty(field)){
						// 检查必填字段格式
						if(keyForImportDouble[i].children[key].ctrr_rsrm_is_needed === 1){
							// postMessage({step: -1, msg: '缺少必填字段：' + keyForImportDouble[i].ctrr_rsrm_name+'表中缺少【'+ field +'】字段，为必须字段，请补充后重新导入'})
							neededFiledErrMsg.push(keyForImportDouble[i].ctrr_rsrm_name+'表中缺少【'+ field +'】字段，为必须字段，请补充后重新导入')
						}
						str += '[' + field + ']、'
					}
				}	
				if(str && neededFiledErrMsg.length !== 0){
					// 如果出现缺少字段的情况，则无需生成普通错误报告了
					// postMessage({step: -1, msg:'缺少字段：【' + keyForImportDouble[i].ctrr_rsrm_name + '】表中缺少以下字段：' + str + '(可能是字段左右存在空格或者回车符)'})
					errorReport.push('【' + keyForImportDouble[i].ctrr_rsrm_name + '】表中缺少以下字段：' + str + '(可能是字段左右存在空格或者回车符)')
				}
			}
			postMessage({step: 4, data: {
				neededFiledErrMsg: neededFiledErrMsg,
				errorReport: errorReport
			}})
			break;
		case 4:
			// step4
			// 第四步：开始全量遍历导入数据
			const excel_to_sqlData = []
			// 必填字段数据错误报告  注：与neededFiledErrMsg区别，一个是判断字段是否存在，一个是字段存在进一步判断字段下的数据是否存在
			const isNeededDataErrMsg = []
			// 下拉框值与开关值错误报告
			const select_switch_fieldErrMsg = []
			// 时间格式错误报告
			const timeErrMsg = []
			// 第四步：开始整理导入数据，对数据进行全量检测 —— 遍历每一个数据
			for(let i = 0; i < keyForImportDouble.length ; i++){
				postMessage({step: -1, msg: '正在遍历表格数据'})
				const getExcelDataSheet = getExcelData[keyForImportDouble[i].ctrr_rsrm_name] // 每次将获取一整张表的数据
				if(getExcelDataSheet){
					for(let j = 0; j < getExcelDataSheet.length; j++){
						let str = '{'
						// 保存主键字段
						let isKeyStr = ''
						for(const key in keyForImportDouble[i].children){
							// 字段结构
							const fieldStructure = keyForImportDouble[i].children[key]
							let d = getExcelDataSheet[j][fieldStructure.ctrr_rsrm_name];
							// 校验必填字段规则：trim()函数用于去掉字符串的头尾空格，除了可以判断一个空值的情况，还可用于判断多个纯空格的情况
							if (typeof d == 'string') {
								d = d.trim()
							}
							if(d){
								// 换行符检测逻辑：转义所有换行符号
								// 换行符转义
								d = d.replace(/\n/g, '\\n') // 由于前段input元素显示也需要转义，所以这里给两个斜杠\\才可正常显示，双引号转义同理
								// 双引号检测逻辑：转义所有双引号
								// 双引号转义
								d = d.replace(/"/g, '\\"')
								// 制表符检测逻辑：转义所有制表符
								// 制表符转义
								d = d.replace(/	/g, '\\t')

								str += '"' + key + '":"' + d + '",'
							}
							// 字段类型规则校验逻辑
							if(d === '' || d === undefined || d === null){
								// 检测是否必须字段
								if( fieldStructure.ctrr_rsrm_is_needed === 1 ){
									// postMessage({step: -1, msg:'必填字段为空：' + keyForImportDouble[i].ctrr_rsrm_name+'表中的【'+ fieldStructure.ctrr_rsrm_name +'】字段为必填字段，第'+ (j+2) +'行值为空，不符合要求，请补充后重新导入'})
									isNeededDataErrMsg.push(keyForImportDouble[i].ctrr_rsrm_name+'表中的【'+ fieldStructure.ctrr_rsrm_name +'】字段为必填字段，第'+ (j+2) +'行值为空，不符合要求，请补充后重新导入')
								}
							}else if( fieldStructure.ctrr_rsrm_type === 2 || fieldStructure.ctrr_rsrm_type === 3){
								// 下拉框值与开关值检测并构造错误报告
								let is_select_switch_err = 1;
								for (let i = 0; i < fieldStructure.ctrr_rsrm_valueFortype.length; i++) {
									const option = fieldStructure.ctrr_rsrm_valueFortype[i];
									if( option === d){
										is_select_switch_err = 0
									}
								}
								if ( is_select_switch_err === 1 ) {
									// postMessage({step: -1, msg:'下拉框字段不符合取值要求：' + keyForImportDouble[i].ctrr_rsrm_name+'表中的【'+ fieldStructure.ctrr_rsrm_name +'】字段，第'+ (j+2) +'行值为'+d+'，数据不符合要求'})
									select_switch_fieldErrMsg.push(keyForImportDouble[i].ctrr_rsrm_name+'表中的【'+ fieldStructure.ctrr_rsrm_name +'】字段，第'+ (j+2) +'行值为'+d+'，数据不符合要求')
								}
							} else if ( fieldStructure.ctrr_rsrm_type === 6) {
								// 日期值检测并构造错误报告
								if(!dateFormatReg.test(d)){
									// postMessage({step: -1, msg:'必填时间字段格式有误：' + keyForImportDouble[i].ctrr_rsrm_name+'表中的【'+ fieldStructure.ctrr_rsrm_name +'】字段，第'+ (j+2) +'行值为'+d+'，数据不符合时间格式要求（yyyy/MM/dd HH:mm:ss）'})
									timeErrMsg.push(keyForImportDouble[i].ctrr_rsrm_name+'表中的【'+ fieldStructure.ctrr_rsrm_name +'】字段，第'+ (j+2) +'行值为'+d+'，数据不符合时间格式要求（yyyy/MM/dd HH:mm:ss）')
								}
							}
							
							// 主键字段值构造，用于最后一步导入时进行数据查重检测
							if( fieldStructure.ctrr_rsrm_is_key === 1 ){
								d && (isKeyStr = '"' + fieldStructure.ctrr_rsrm_id + '":"' + d + '"')
							}
						}

						str = str.substring(0, str.length-1)
						str += '}'
						excel_to_sqlData.push({
							ctrr_rsrm_id_l2: keyForImportDouble[i].ctrr_rsrm_id,
							ctrr_rsrmD_data: str,
							isKeyStr: isKeyStr // 主键字段构造的字符串
						})
					}
				}
			}
			postMessage({step: -1, msg: '处理完毕！'})
			postMessage({step: 5, data: {
				excel_to_sqlData: excel_to_sqlData,
				isNeededDataErrMsg: isNeededDataErrMsg,
				select_switch_fieldErrMsg: select_switch_fieldErrMsg,
				timeErrMsg: timeErrMsg
			}})
			break;
	}
}, false);