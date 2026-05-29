<?php

use tws\helpers\Url;

$chatVisibleRaw = Yii::$app->request->get('visible');
if ($chatVisibleRaw === null || $chatVisibleRaw === '') {
	$chatVisibleRaw = Yii::$app->settings->get('chatVisible', 'interface');
}
if ($chatVisibleRaw === null || $chatVisibleRaw === '') {
	$chatVisibleRaw = Yii::$app->settings->get('chatVisible');
}
$isChatVisible = filter_var($chatVisibleRaw, FILTER_VALIDATE_BOOLEAN);

$chatExpandedRaw = Yii::$app->request->get('expanded');
if ($chatExpandedRaw === null || $chatExpandedRaw === '') {
	$chatExpandedRaw = Yii::$app->settings->get('chatExpanded', 'interface');
}
if ($chatExpandedRaw === null || $chatExpandedRaw === '') {
	$chatExpandedRaw = Yii::$app->settings->get('chatExpanded');
}
$isChatExpanded = filter_var($chatExpandedRaw, FILTER_VALIDATE_BOOLEAN);

?>

<!-- Chat Bubble Toggle Button -->
<button class="chat-toggle chat-bubble" style="<?= Yii::$app->request->get('color') ? 'background-color: #' . Yii::$app->request->get('color') . ';' : ''; ?>">
	<i class="fa fa-comments-o"></i>
</button>

<!-- Chat Container -->
<div class="chat-container <?= $isChatExpanded ? 'expanded' : ''; ?>" style="<?= $isChatVisible ? 'display: flex;' : ''; ?>">
	<div class="chat-header" style="<?= Yii::$app->request->get('color') ? 'background-color: #' . Yii::$app->request->get('color') . ';' : ''; ?>">
		<?= Yii::$app->name ?> <span class="chat-toggle chat-chevron"><i class="fa fa-chevron-down"></i></span> <?= $isChatExpanded ? '<span class="chat-resize chat-compress"><i class="fa fa-compress"></i></span>' : '<span class="chat-resize chat-expand"><i class="fa fa-expand"></i></span>'; ?>
	</div>
	<div class="chat-body <?= $isChatExpanded ? 'expanded' : ''; ?>" id="chat-body">
	</div>
	<div class="chat-input-container">
		<input type="text" class="chat-input" id="chat-input" placeholder="<?= Yii::t('label', 'Message') ?>...">
		<button class="send-button" id="send-button" style="<?= Yii::$app->request->get('color') ? 'background-color: #' . Yii::$app->request->get('color') . ';' : ''; ?>"><i class="fa fa-send"></i></button>
	</div>
</div>

<?php
$this->registerJs('
    $(document).ready(function() {
    
        // Toggle chat container display on chat-toggle click
        $(".chat-toggle").click(function(e) {
             e.preventDefault();
             if ($(".chat-container").css("display") === "none") {
                 $(".chat-container").css("display", "flex");
             } else {
                 $(".chat-container").css("display", "none");
             }
             sendDimensions();
        });
    
        // Toggle expanded/compressed state on chat-resize span click
        $(".chat-resize").click(function(e) {
            e.preventDefault();
            // Toggle the "expanded" class on both the chat container and the chat body
            $(".chat-container, #chat-body").toggleClass("expanded");
            
            // Update the resize icon
            if ($(".chat-container").hasClass("expanded")) {
                $(".chat-resize").removeClass("chat-expand").addClass("chat-compress");
                $(".chat-resize i").removeClass("fa-expand").addClass("fa-compress");
		          const parentWidth = window.parent.innerWidth - 10;
                  const parentHeight = window.parent.innerHeight - 100;
                  sendDimensions(parentHeight, parentWidth);
            } else {
                $(".chat-resize").removeClass("chat-compress").addClass("chat-expand");
                $(".chat-resize i").removeClass("fa-compress").addClass("fa-expand");
                sendDimensions();
            }
        });
    
        // Existing functionality: adjust chat-body padding based on content
        function updatePadding() {
            if ($("#chat-body").children().length > 0) {
                $("#chat-body").css("padding", "12px");
            } else {
                $("#chat-body").css("padding", "0");
            }
        }
        
        // Function to calculate and send dimensions to the parent window
        function sendDimensions(height, width) {
            var $toggle = $(".chat-toggle");
            var $container = $(".chat-container");
            var toggleHeight = $toggle.outerHeight();
            var containerHeight = $container.outerHeight();
            var containerWidth = $container.outerWidth();
            var isVisible = $container.is(":visible");
            
            var message = {
                type: "resize",
                height: height != null ? height : toggleHeight + containerHeight + 50,
                width: width != null ? width : containerWidth + 10,
                visible: isVisible
            };
            
            window.parent.postMessage(message, "*");
        }
    
        $("#send-button").click(function() {
            sendMessage();
        });
    
        $("#chat-input").keypress(function(event) {
            if (event.which === 13) {
                sendMessage();
            }
        });
    
        function sendMessage() {
            var userMessage = $("#chat-input").val().trim();
            if (userMessage !== "") {
                $("#chat-body").append("<div class=\'chat-message user-message\'>" + userMessage + "</div>");
                updatePadding();
                $("#chat-input").val("");
    
                // Append a bot "thinking" message with a loader
                var botMessageDiv = $("<div class=\'chat-message bot-message\'><span class=\'loader\' style=\'' . (Yii::$app->request->get('color') ? 'border: 4px solid #' . Yii::$app->request->get('color') . ';' : '') . '\'><span class=\'loader-inner\' style=\'' . (Yii::$app->request->get('color') ? 'background-color: #' . Yii::$app->request->get('color') . ';' : '') . '\'></span></span></div>");
                $("#chat-body").append(botMessageDiv);
                sendDimensions();
                $("#chat-body").scrollTop($("#chat-body")[0].scrollHeight);
    
                // Send the user message to the backend
                $.ajax({
                    url: "' . Url::to(['/embed/chat']) . '",
                    type: "POST",
                    data: { prompt: userMessage },
                    success: function(response) {
                        if (response.reply) {
                            botMessageDiv.text(response.reply);
                        } else {
                            botMessageDiv.text("Error: No response received.");
                        }
                        updatePadding();
                        sendDimensions();
                        $("#chat-body").scrollTop($("#chat-body")[0].scrollHeight);
                    },
                    error: function(xhr, status, error) {
                        botMessageDiv.text("Error: Unable to get a response. " + error);
                        updatePadding(); 
                        sendDimensions();
                    }
                });
            }
        }
    
        // Initial adjustments
        updatePadding();
        sendDimensions();
    });
', \yii\web\View::POS_READY);
?>
