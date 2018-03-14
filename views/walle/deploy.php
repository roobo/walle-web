<?php
/**
 * @var yii\web\View $this
 */
$this->title = yii::t('walle', 'deploying');
use \app\models\Task;
use yii\helpers\Url;

$hosts = \app\components\GlobalHelper::str2arr($task->project->hosts);
$taskManger = new \app\components\TaskStateManager();
$isRunning = $taskManger->isRunningTask($task->id);
?>
<style>
    .status > span {
        float: left;
        font-size: 12px;
        width: 14%;
        text-align: right;
    }
    .btn-deploy {
        margin-left: 30px;
    }
    .btn-return {
        /*float: right;*/
        margin-left: 30px;
    }
</style>
<div class="box" style="height: 100%">
    <input type="hidden" id="hosts" name="hosts" value="<?= $task->project->hosts ?>"/>
    <h4 class="box-title header smaller red">
            <i class="icon-map-marker"></i><?= \Yii::t('w', 'conf_level_' . $task->project['level']) ?>
            -
            <?= $task->project->name ?>
            ：
            <?= $task->title ?>
            （<?= $task->project->repo_mode . ':' . $task->branch ?> <?= yii::t('walle', 'version') ?><?= $task->commit_id ?>）
            <?php if (in_array($task->status, [Task::STATUS_PASS, Task::STATUS_FAILED])) { ?>
                <button type="submit" id="" class="btn btn-primary btn-deploy" data-id="<?= $task->id ?>"><?= yii::t('walle', 'deploy') ?></button>
            <?php } ?>
            <a class="btn btn-success btn-return" href="<?= Url::to('@web/task/index') ?>"><?= yii::t('walle', 'return') ?></a>
    </h4>
    <h4 class="box-title header smaller red">
        <?= yii::t('task', 'remark') ?>&nbsp;:&nbsp;<?= $task->remark ?>
    </h4>
    <div class="status">
        <span><i class="fa fa-circle-o text-yellow step-1"></i><?= yii::t('walle', 'process_detect') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-2"></i><?= yii::t('walle', 'process_pre-deploy') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-3"></i><?= yii::t('walle', 'process_checkout') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-4"></i><?= yii::t('walle', 'process_post-deploy') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-5"></i><?= yii::t('walle', 'process_rsync') ?></span>

        <?php if ($task->project->slb_status) { ?>
            <span style="width: 28%"><i class="fa fa-circle-o text-yellow step-6"></i><?= yii::t('walle', 'process_test_swich_flow') ?></span>
        <?php } else { ?>
            <span style="width: 28%"><i class="fa fa-circle-o text-yellow step-7"></i><?= yii::t('walle', 'process_update') ?></span>
        <?php } ?>
    </div>
    <div style="clear:both"></div>
    <div class="progress progress-small progress-striped active">
        <div class="progress-bar progress-status progress-bar-success" style="width: <?= $task->status == Task::STATUS_DONE ? 100 : 0 ?>%;"></div>
    </div>

    <div class="alert alert-block alert-success result-success" style="<?= $task->status != Task::STATUS_DONE ? 'display: none' : '' ?>">
        <h4><i class="icon-thumbs-up"></i><?= yii::t('walle', 'done') ?></h4>
        <p><?= yii::t('walle', 'done praise') ?></p>

    </div>

    <div class="alert alert-block alert-danger result-failed" style="display: none">
        <h4><i class="icon-bell-alt"></i><?= yii::t('walle', 'error title') ?></h4>
        <span class="error-msg">
        </span>
        <br><br>
        <i class="icon-bullhorn"></i><span><?= yii::t('walle', 'error todo') ?></span>
    </div>

    <div style="display: <?= $task->project->slb_status ? '' : 'none' ?>">
        <?php if ($task->project->slb_status && $task->action == Task::ACTION_ONLINE) { ?>
            <?= yii::t('walle', 'machine list') ?><br/>
            <br/>
            <button id="skip_manual_test" type="button" class="btn btn-primary"
                    style="display: <?= $isRunning ? '' : 'none' ?>"
                    onclick="skipManualTest('<?= $task->id ?>')" <?= $taskManger->getTaskManualTestAllPass($task->id) ? 'disabled' : '' ?> >
                skip manual test
            </button>&nbsp;<br/><br/>

            <?php foreach ($hosts as $host) { ?>
                <?= $host ?>
                <div class="status">
                    <span style="width: 28%"><i class="fa fa-circle-o text-yellow step-1"></i><?= yii::t('walle', 'sub status 1') ?></span>
                    <span style="width: 28%"><i class="fa fa-circle-o text-yellow step-2"></i><?= yii::t('walle', 'sub status 2') ?></span>
                    <span style="width: 28%"><i class="fa fa-circle-o text-yellow step-3"></i><?= yii::t('walle', 'sub status 3') ?></span>
                </div>
                <div id="btn_container_<?= str_replace('.','',$host) ?>"
                     style="display: <?= $taskManger->getStatus($task->id, $host) == \app\components\TaskStateManager::STATE_DOING_MANUAL_TEST ? '' : 'none' ?>">
                    &nbsp;&nbsp;&nbsp;
                    <button type="button" class="btn btn-primary"
                            onclick="pass_click('<?= $host ?>','<?= \app\components\TaskStateManager::getManualResultKey($task->id, $host) ?>')">
                        pass
                    </button>&nbsp;
                    <button type="button" class="btn btn-primary"
                            onclick="fail_click('<?= $host ?>','<?= \app\components\TaskStateManager::getManualResultKey($task->id, $host) ?>')">
                        fail
                    </button>
                </div>
                <div style="clear:both"></div>
                <div class="progress progress-mini progress-striped active" style="width: 84%">
                    <div class="progress-bar progress-bar-success" id="progress_<?= str_replace('.','',$host) ?>"
                         style="width: 0%;"></div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">

    function skipManualTest(taskId) {
        $.get('/walle/manual-test-pass', {
            taskId: taskId
        }, function (o) {
            if (o.code != 0) {
                $('#skip_manual_test').removeClass('disabled');
                alert('code:' + o.code + ' msg:' + o.msg);
            } else {
                $('#skip_manual_test').addClass('disabled');
                alert('skip all manual test success');
            }
        });
    }

    function pass_click(host,randomKey) {
        // alert(host+" "+ randomKey);

        $.get('/walle/notify-test-result', {
            success: true,
            randomKey: randomKey
        }, function (o) {
            // alert(JSON.stringify(o));
            if (o.code != 0) {
                alert('code:' + o.code + ' msg:' + o.msg);
            } else {
                host = host.replace(/\./g, '');
                // $('#btn_container_' + host).hide();
            }
        });
    }

    function fail_click(host,randomKey) {

        $.get('/walle/notify-test-result', {
            success: false,
            randomKey: randomKey
        }, function (o) {
            // alert(JSON.stringify(o));
            if (o.code != 0) {
                alert('code:' + o.code + ' msg:' + o.msg);
            } else {
                host = host.replace(/\./g, '');
                // $('#btn_container_' + host).hide();
            }
        });
    }

    $(function() {
        var task_id = $('.btn-deploy').data('id');

        function updateSubProgress() {
            var hosts = $('#hosts').val();
            // alert(hosts + " " + taskId);
            $.get('/walle/get-task-process', {
                taskId: task_id,
                hosts: hosts
            }, function (o) {
                var array = o.data;
                // alert(JSON.stringify(o));
                for (var i = 0; i < array.length; i++) {
                    var result = array[i];
                    var host = result.host;
                    host = host.replace(/\./g,'');
                    if (0 != result.progress) {
                        $('#progress_' + host).attr('aria-valuenow', result.progress).width(result.progress + '%');
                    }

                    var manualPass = <?=$taskManger->getTaskManualTestAllPass($task->id)?>;
                    if (result.status == <?=\app\components\TaskStateManager::STATE_DOING_MANUAL_TEST?> && manualPass <= 0) {
                        $('#btn_container_' + host).show();
                        // $('#skip_manual_test').show();
                    } else {
                        $('#btn_container_' + host).hide();
                    }

                    if (result.progress == 100) {
                        $('#progress_' + host).removeClass('progress-bar-striped').addClass('progress-bar-success');
                        $('#progress_' + host).parent().removeClass('progress-striped');
                    }

                    if (result.status == <?=\app\components\TaskStateManager::STATE_DO_AUTO_TEST_FAILED?> ||
                        result.status == <?=\app\components\TaskStateManager::STATE_UPDATE_SERVER_FAILED?> ||
                        result.status == <?=\app\components\TaskStateManager::STATE_DOING_MANUAL_TEST_FAILED?> ) {
                        $('#progress_' + host).removeClass('progress-bar-success').addClass('progress-bar-danger');
                    } else {
                        $('#progress_' + host)
                            .removeClass('progress-bar-danger progress-bar-striped')
                            .addClass('progress-bar-success');
                    }
                }

                setTimeout(updateSubProgress, 1000)
            });
        }

        function getProcess() {
            $.get("<?= Url::to('@web/walle/get-process?taskId=') ?>" + task_id, function (o) {
                var data = o.data;
                var action = '';
                var detail = '';
                if (0 != data.percent) {
                    $('.progress-status').attr('aria-valuenow', data.percent).width(data.percent + '%');
                }
                // 执行失败
                if (0 == data.status) {
                    $('.step-' + data.step).removeClass('text-yellow').addClass('text-red');
                    $('.progress-status').removeClass('progress-bar-success').addClass('progress-bar-danger');
                    detail = o.msg + ':' + data.memo + '<br>' + data.command;
                    $('.error-msg').html(action + detail);
                    $('.result-failed').show();
                    // $('.btn-deploy').removeClass('disabled');
                    $('.btn-deploy').attr('disabled', false);
                    $('#skip_manual_test').attr('disabled', true);
                    return;
                } else {
                    $('.progress-status')
                        .removeClass('progress-bar-danger progress-bar-striped')
                        .addClass('progress-bar-success');
                }
                if (100 == data.percent) {
                    $('.progress-status').removeClass('progress-bar-striped').addClass('progress-bar-success');
                    $('.progress-status').parent().removeClass('progress-striped');
                    $('.result-success').show();
                    $('#skip_manual_test').attr('disabled', true);
                } else {
                    setTimeout(getProcess, 600);
                }
                for (var i = 1; i <= data.step; i++) {
                    $('.step-' + i).removeClass('text-yellow text-red')
                        .addClass('text-green progress-bar-striped')
                }
            });
        }

        var isRunning = <?=$isRunning?>;

        if (isRunning > 0) {
            $('.btn-deploy').attr('disabled', true);

            $('.progress-status').attr('aria-valuenow', 10).width('10%');
            $('.result-failed').hide();
            setTimeout(getProcess, 600);
            setTimeout(updateSubProgress, 1000);
        } else {
            $('.btn-deploy').attr('disabled', false);
        }

        $('.btn-deploy').click(function() {
            $this = $(this);
            // $this.addClass('disabled');
            $this.attr('disabled', true);
            var task_id = $(this).data('id');
            var action = '';
            var detail = '';
            var timer;
            $.post("<?= Url::to('@web/walle/start-deploy') ?>", {taskId: task_id}, function(o) {
                action = o.code ? o.msg + ':' : '';
                if (o.code != 0) {
                    $('.progress-status').removeClass('progress-bar-success').addClass('progress-bar-danger');
                    $('.error-msg').text(action + detail);
                    $('.result-failed').show();
                    // $this.removeClass('disabled');
                    $this.attr('disabled', false);
                }
            });
            $('.progress-status').attr('aria-valuenow', 10).width('10%');
            $('.result-failed').hide();
            $('#skip_manual_test').show();
            $('#skip_manual_test').attr('disabled', false);

            setTimeout(getProcess, 600);
            setTimeout(updateSubProgress, 1000);
        });

        var _hmt = _hmt || [];
        (function() {
            var hm = document.createElement("script");
            hm.src = "//hm.baidu.com/hm.js?5fc7354aff3dd67a6435818b8ef02b52";
            var s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(hm, s);
        })();
    })
</script>
