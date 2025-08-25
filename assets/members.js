import './style/members.less';

$(document).ready(function () {
  $('.reserve').on('click', function (e) {
    const eventId = e.currentTarget.getAttribute("data");
    let route = 'event_reserve';
    let renameUrl = Routing.generate(route, { ["event" + 'Id']: eventId }, true);
    $.post(renameUrl, {
    }, function (r) {
      window.location.reload()
    }, 'JSON');
  });
  $('.dereserve').on('click', function (e) {
    const eventId = e.currentTarget.getAttribute("data");
    let route = 'event_dereserve';
    let renameUrl = Routing.generate(route, { ["event" + 'Id']: eventId }, true);
    $.post(renameUrl, {
    }, function (r) {
      window.location.reload()
    }, 'JSON');
  });
})