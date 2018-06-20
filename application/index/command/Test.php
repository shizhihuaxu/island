<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use app\index\model\Daily;

class Test extends Command
{
    protected function configure(){
        $this->setName('Test')->setDescription("计划任务 Test");
    }

    protected function execute(Input $input, Output $output){
        $output->writeln('Date Crontab job start...');
        /*** 这里写计划任务列表集 START ***/

        $this->test();

        /*** 这里写计划任务列表集 END ***/
        $output->writeln('Date Crontab job end...');
    }

    private function test(){
        // 连接数据库
        $DB=Db::connect([
            // 数据库类型
            'type'        => 'mysql',
            // 数据库连接DSN配置
            'dsn'         => '',
            // 服务器地址
            'hostname'    => '127.0.0.1',
            // 数据库名
            'database'    => 'myweb',
            // 数据库用户名
            'username'    => 'root',
            // 数据库密码
            'password'    => '1409044123',
            // 数据库连接端口
            'hostport'    => '3306',
            // 数据库连接参数
            'params'      => [],
            // 数据库编码默认采用utf8
            'charset'     => 'utf8',
            // 数据库表前缀
            'prefix'      => 'think_',
        ]);
        // 
        $dailyArr = $DB->name('daily')->limit(1)->select();

        $content =  $dailyArr[0]["content"];
        $id =  $dailyArr[0]["id"];
        $DB->name('daily')->where('id',$id)->setField('status', '1');//修改状态为已读
    }
}