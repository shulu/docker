/**
 * Created by benson on 10/15/15.
 */
    var $createTopicModal = $("#create_topic");
    var $topicIdInput = $("#topic_id");
    var $topicNameInput = $("#topic_name");
    var $topicDescInput = $('#description');
    var $creatorInput = $("#creator");
    var $opMark = $('#op_mark');
    var $originImageWrap = $("#origin_index_image_wrap");
    var $originImage = $("#origin_index_image");
    var $property = $('#property');
    var $categories = $('.categories');
    var $manager = $('#manager');
    $createTopicModal.on('hidden.bs.modal', function (e) {
        $topicNameInput.val('');
        $creatorInput.val('');
        $opMark.val('');
        $topicIdInput.val('');
        $topicDescInput.val('');
        $originImageWrap.hide();
        $property.val('');
        $manager.val('');
        $categories.each(function(index, elem) {
            $(elem).attr('checked', false);
        });
    });

    function topicEdit(target) {
        $.ajax($('#topic_fetch_url').val(), {
            type: "get",
            dataType: "json",
            data: {
                id: $(target).data("id")
            },
            success: function (data) {
                var topic = data.topic;
                var property = data.property;
                $createTopicModal.modal();
                $topicNameInput.val(topic.title);
                $creatorInput.val(topic.creatorId);
                $opMark.val(topic.opMark);
                $topicIdInput.val(topic.id);
                $topicDescInput.val(topic.description);
                $manager.val(topic.managerId);
                $property.val(property.category_id);
                if (topic.indexImageUrl) {
                    $originImage.attr("src", topic.indexImageUrl);
                    $originImageWrap.show();
                }
                $categories.each(function(index, elem) {
                    var id = $(elem).val();
                    if (-1 != $.inArray(id, data.categories)) {
                        $(elem).attr('checked', 'checked');
                    }
                });
            }
        });
        return false;
    }

    function topicRemove(target) {
        if (confirm('确定永久删除次元？')) {
            var topicId = $(target).data('id');
            var $form = $('<form method="post"></form>');
            $form.attr('action', $('#topic_remove_url').val());
            $form.append('<input type="hidden" name="topic_id" value="' + topicId + '">');
            $('body').append($form);
            $form.submit();
        }

        return false;
    }

    function topicHide(target) {
        if (confirm('确定隐蔽/取消隐蔽该次元？')) {
            var topicId = $(target).data('id');
            var $form = $('<form method="post"></form>');
            $form.attr('action', $('#topic_hide_url').val());
            $form.append('<input type="hidden" name="topic_id" value="' + topicId + '">');
            $('body').append($form);
            $form.submit();
        }

        return false;
    }

    //$(".topic-edit").click(function () {
    //    $.ajax($('#topic_fetch_url').val(), {
    //        type: "get",
    //        dataType: "json",
    //        data: {
    //            id: $(this).data("id")
    //        },
    //        success: function (data) {
    //            var topic = data.topic;
    //            var property = data.property;
    //            $createTopicModal.modal();
    //            $topicNameInput.val(topic.title);
    //            $creatorInput.val(topic.creatorId);
    //            $opMark.val(topic.opMark);
    //            $topicIdInput.val(topic.id);
    //            $topicDescInput.val(topic.description);
    //            $manager.val(topic.managerId);
    //            $property.val(property.category_id);
    //            if (topic.indexImageUrl) {
    //                $originImage.attr("src", topic.indexImageUrl);
    //                $originImageWrap.show();
    //            }
    //            $categories.each(function(index, elem) {
    //                var id = $(elem).val();
    //                if (-1 != $.inArray(id, data.categories)) {
    //                    $(elem).attr('checked', 'checked');
    //                }
    //            });
    //        }
    //    });
    //});
    //$('.topic-remove').click(function() {
    //    if (confirm('确定永久删除次元？')) {
    //        var topicId = $(this).data('id');
    //        var $form = $('<form method="post"></form>');
    //        $form.attr('action', $('#topic_remove_url').val());
    //        $form.append('<input type="hidden" name="topic_id" value="' + topicId + '">');
    //        $('body').append($form);
    //        $form.submit();
    //    }
    //
    //    return false;
    //});
    //$('.topic-hide').click(function() {
    //    if (confirm('确定隐蔽/取消隐蔽该次元？')) {
    //        var topicId = $(this).data('id');
    //        var $form = $('<form method="post"></form>');
    //        $form.attr('action', $('#topic_hide_url').val());
    //        $form.append('<input type="hidden" name="topic_id" value="' + topicId + '">');
    //        $('body').append($form);
    //        $form.submit();
    //    }
    //
    //    return false;
    //});