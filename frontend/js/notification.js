const STORAGE_KEY_PRESENTED_NOTIFICATIONS = 'presented-notifications';

function askNotificationPermission() {
	if(!Notification) {
		emitMessage(LANG['desktop_notifications_not_supported'], '', MESSAGE_TYPE_ERROR);
		return;
	}
	if(Notification.permission !== 'granted') {
		Notification.requestPermission().then(function(result) {
			if(result === 'denied') {
				emitMessage(LANG['desktop_notifications_denied'], '', MESSAGE_TYPE_WARNING);
			}
			if(result === 'granted') {
				// start watching for notifications
				setInterval(refreshNotificationInfo, 5000);
			}
		});
	} else {
		emitMessage(LANG['desktop_notifications_already_permitted'], '', MESSAGE_TYPE_INFO);
	}
}

// reset previous notification state
localStorage.setItem(STORAGE_KEY_PRESENTED_NOTIFICATIONS, null);
// permission already granted
// automatically start watching for notifications
if(Notification && Notification.permission === 'granted') {
	desktopNotificationCheck = setInterval(refreshNotificationInfo, 5000);
}

var notificationInfo = null;
function refreshNotificationInfo() {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState != 4) return;
		if(this.status == 200) {
			checkNotification(JSON.parse(this.responseText));
		} else if(this.status == 401) { // stop periodic query if user is logged out
			clearInterval(desktopNotificationCheck);
		}
	};
	xhttp.open('GET', 'ajax-handler/notification-info.php', true);
	xhttp.send();
}

function checkNotification(newNotificationInfo) {
	if(notificationInfo != null) {
		newNotificationInfo['job_container'].forEach(function(item1) {
			notificationInfo['job_container'].forEach(function(item2) {
				if(item1.id == item2.id && item1.state != item2.state) {
					notify('['+item1.state_description+'] '+item1.name, LANG['job_container_status_changed'], 'img/job.dyn.svg',
						'index.php?view=job-containers&id='+encodeURIComponent(item1.id),
						'job#'+item1.id+'#'+item1.state
					);
				}
			});
		});
	}
	notificationInfo = newNotificationInfo;
}

function notify(title, body, icon, link, tag) {
	// check if notification was already presented
	let presentedNotifications = JSON.parse(localStorage.getItem(STORAGE_KEY_PRESENTED_NOTIFICATIONS));
	if(presentedNotifications == null) presentedNotifications = [];
	if(presentedNotifications.indexOf(tag) != -1) return;
	presentedNotifications.push(tag);
	localStorage.setItem(STORAGE_KEY_PRESENTED_NOTIFICATIONS, JSON.stringify(presentedNotifications));

	// show notification
	if(Notification.permission !== 'granted') return;
	var notification = new Notification(title, {
		icon: icon, body: body, tag: tag
	});
	notification.onclick = function() {
		window.open(link);
	};
}
