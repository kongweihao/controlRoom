let request = require('request');
// 领导与组长
let leaderMsg = {
  zky:'13902220716',
  zpf:'13802881289',
  czy:'13802880455',
  hjl:'13802881498'
}
let yesterdayMsg = '【昨日夜班人员信息】'
let todayMsg = '【今日值班人员信息】'
let tomorrowMsg = '【明天值班人员信息】'
// 固定数据
request('http://localhost/controlRoom/customer/getTMMonitor', function(err, response, body){
  //err 当前接口请求错误信息
  //response 一般使用statusCode来获取接口的http的执行状态
  //body 当前接口response返回的具体数据 返回的是一个jsonString类型的数据 
  //需要通过JSON.parse(body)来转换
  if(!err && response.statusCode == 200){
    //todoJSON.parse(body)
    const res = JSON.parse(body);
    // yesterdayMsg += '[夜班]'
    // for(let i = 0; i < res[0].length; i++) {
    //   if (res[0][i]['is_night'] == 1) {
    //     yesterdayMsg += res[0][i]['monitor_post_name'] + '：'+res[0][i]['member_name']+'、'
    //   }
    // }

    todayMsg += '白班：'
    for(let i = 0; i < res[1].length; i++) {
      if (res[1][i]['is_day'] == 1 && res[1][i]['is_night'] == 0) {
        todayMsg += res[1][i]['member_name']+'、'
      }
    }
    todayMsg = todayMsg.substr(0, todayMsg.length-1) // 删除最后一个字符串
    todayMsg += '夜班：'
    for(let i = 0; i < res[1].length; i++) {
      if (res[1][i]['is_night'] == 1 && res[1][i]['is_day'] == 0) {
        todayMsg += res[1][i]['member_name']+'、'
      }
    }
    todayMsg = todayMsg.substr(0, todayMsg.length-1) // 删除最后一个字符串
    todayMsg += '支撑：'
    for(let i = 0; i < res[1].length; i++) {
      if (res[1][i]['is_night'] == 1 && res[1][i]['is_day'] == 1) {
        todayMsg += res[1][i]['member_name']+'、'
      }
    }
    todayMsg = todayMsg.substr(0, todayMsg.length-1) // 删除最后一个字符串
    
    // tomorrowMsg += '[白班]'
    // for(let i = 0; i < res[2].length; i++) {
    //   if (res[2][i]['is_day'] == 1) {
    //     tomorrowMsg += res[2][i]['monitor_post_name'] + '：'+res[2][i]['member_name']+'、'
    //   }
    // }
    // tomorrowMsg += '[夜班]'
    // for(let i = 0; i < res[2].length; i++) {
    //   if (res[2][i]['is_night'] == 1) {
    //     tomorrowMsg += res[2][i]['monitor_post_name'] + '：'+res[2][i]['member_name']+'、'
    //   }
    // }
    console.log(yesterdayMsg,todayMsg,tomorrowMsg);
  }
})
// --------------开启短信定时任务↓------------------------------------------------------------------

const schedule = require('node-schedule');

const  scheduleCronstyle = ()=>{
  //每天早上8点发送一次短信:
  // 对象1：领导+组长  内容：昨晚夜班人员信息，今天白夜班人员信息
  // 对象2：当天白班值班同事   内容：提醒今天值班
    schedule.scheduleJob('0 0 8 * * *',()=>{
      // console.log('scheduleCronstyle:' + new Date());
      // console.log('8:00');
    }); 

  //每天下午16点发送一次短信:
  // 对象1：当天夜班值班同事   内容：提醒今晚值班
    schedule.scheduleJob('* * * * * *',()=>{
      // console.log('16:00');
  }); 

  //每天晚上22点30发送一次短信:
  // 对象1：第二天值班同事（含白夜班）   内容：提醒明天值班
    schedule.scheduleJob('* * * * * *',()=>{
      // console.log('22:30');
  }); 
}

scheduleCronstyle();
// 规则参数讲解 *代表通配符
// *  *  *  *  *  *
// ┬ ┬ ┬ ┬ ┬ ┬
// │ │ │ │ │  |
// │ │ │ │ │ └ day of week (0 - 7) (0 or 7 is Sun)
// │ │ │ │ └───── month (1 - 12)
// │ │ │ └────────── day of month (1 - 31)
// │ │ └─────────────── hour (0 - 23)
// │ └──────────────────── minute (0 - 59)
// └───────────────────────── second (0 - 59, OPTIONAL)
// 1、每个参数还可以传入数值范围，如：1-10 * * * * *
// 2、取消定时器：调用 定时器对象的cancl()方法即可
// schedule.cancl()
// --------------开启短信定时任务↑------------------------------------------------------------------
