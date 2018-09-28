/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/4/16 16:37
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/
$(function () {
    var bar = $('.bar');
    var percent = $('.percent');
    var status = $('#status');
    $('form').ajaxForm({
        beforeSerialize: function () {
            //alert("表单数据序列化前执行的操作！");
            //$("#txt2").val("java");//如：改变元素的值
        },
        beforeSubmit: function () {
            if ($("input[type='file']")[0].files[0] == undefined) {
                alert("请选择要识别的图片");
            }
            var limitFileSize = 9;
            var filesize = $("input[type='file']")[0].files[0].size / 1024 / 1024;
            console.log(filesize);
            if (filesize > limitFileSize) {
                alert("文件大小超过限制，最大限制为：" + limitFileSize + 'M');
                return false;
            }
        },
        beforeSend: function () {
            status.empty();
            var percentVal = '0%';
            bar.width(percentVal);
            percent.html(percentVal);
        },
        uploadProgress: function (event, position, total, percentComplete) {
            //position 已上传了多少
            //total 总大小
            //已上传的百分数
            var percentVal = percentComplete + '%';
            bar.width(percentVal)
            percent.html(percentVal);
            console.log(percentVal, position, total);
        },
        success: function (data) {
            console.log('success ---- ' + data.code);
            var percentVal = '100%';
            bar.width(percentVal);
            percent.html(percentVal);
            if (data.code == 200) {
                alert('系统自动识别成功');
            } else {
                alert(data.msg);
            }
        },
        error: function (err) {
            alert('系统自动识别异常');
        },
        complete: function (xhr) {
            var $data = JSON.parse(xhr.responseText); //由JSON字符串转换为JSON对象，json转成object
            console.log("------xhr.responseText------" + xhr.responseText);
            console.log("-------dasdasdsa-----" + $data);
            var $content;
            if ($data.code == 200) {
                $content = $data.data.content;
            } else if ($data.code == 500) {
                $content = '<h5 style="color:#dc143c;">' + $data.msg + '</h5>';
            } else {
                $content = '未知错误，请检查表单提交';
            }
            $("#img-content").empty();
            $("#img-content").prepend($content);
        }
    });
});