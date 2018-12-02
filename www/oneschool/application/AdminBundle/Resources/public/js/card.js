/**
 * Created by benson on 10/16/15.
 */
function delPost(target) {
    var postId = $(target).attr("post_id");
    var $posts = $(".card-" + postId);

    if (!confirm("确定删除？")) {
        return false;
    }

    $.ajax("{{ path('lychee_admin_contentaudit_freeze') }}", {
        type: "POST",
        dataType: "json",
        data: {
            post_id: postId
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if (errorThrown) {
                alert(errorThrown);
            } else {
                alert("发生未知错误");
            }
        },
        success: function(data) {
            var $btns = $posts.find(".operation button");
            $btns.attr("disabled", "disabled");
            $btns.html("被 " + data.manager + " 删除");
            $posts.addClass('card-deleted');
        }
    });
}

function showPostDetail(target) {
    var $btn = $(target);
    var url = "/post_manager/" + $btn.data('post-id');
    window.open(url, "_blank");
}

function switchFavor(target) {
    var postId = $(target).data("id");
    $.ajax("{{ path('lychee_admin_postmanager_togglefavor') }}", {
        type: "post",
        dataType: "json",
        data: {
            id: postId
        },
        success: function(data) {
            if ("undefined" !== typeof data.deleted) {
                var $btns = $(".card-" + postId + " button.favor span.glyphicon");
                if (data.deleted === 0) {
                    $btns.removeClass("glyphicon-heart-empty").addClass("glyphicon-heart");
                } else {
                    $btns.removeClass("glyphicon-heart").addClass("glyphicon-heart-empty");
                }
            }
        }
    });
}

function recommend(target) {
    if (confirm("确定置顶？")) {
        var postId = $(target).data("post-id");
        $.ajax("{{ path('lychee_admin_postmanager_top') }}", {
            type: "post",
            dataType: "json",
            data: {
                id: postId
            },
            success: function (data) {
                alert("置顶成功");
                $(".card-" + postId).find("button.recommend").remove();
            }
        });
    }

    return false;
}