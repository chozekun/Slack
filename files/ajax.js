$("#webhook_test").click(function (event) {
  event.preventDefault();
  const url = $(this).attr("href");
  const url_webhook = $("#url_webhook").val();
  $.ajax({
    type: "post",
    url: url,
    data: { "url_webhook": url_webhook },
    cache: false,
    success: function (data) {
      if (data) {
        alert("Error: " + data);
      } else {
        alert("Success!");
      }
    },
    error: function (_obj, status, error) {
      alert("Error " + status + ": " + error);
    },
  });
});

$("a.preview").click(function (event) {
  event.preventDefault();
  const url = $(this).attr("href");
  const btn_id = $(this).attr("id");
  const [action, ...rest] = btn_id.split("_");
  const name = rest.join("_");
  const type = name.replace(/_format$/, "");
  const format = $("#" + name).val();
  const preview = $("#" + name + "_preview");
  const id = $("#" + type + "_id").val();
  $.ajax({
    type: "post",
    url: url,
    data: { "action": action, "format": format, "type": type, "id": id },
    cache: false,
    success: function (data) {
      preview.text(data);
    },
    error: function (_obj, status, error) {
      alert("Error " + status + ": " + error);
    },
  });
});

$("a.restore").click(function (event) {
  event.preventDefault();
  const url = $(this).attr("href");
  const btn_id = $(this).attr("id");
  const field = btn_id.replace(/^restore_/, "");
  const target = $("#" + field);
  $.ajax({
    type: "post",
    url: url,
    data: { "field": field },
    cache: false,
    success: function (data) {
      target.val(data);
      target.css("height", target.prop("scrollHeight") + "px");
    },
    error: function (_obj, status, error) {
      alert("Error " + status + ": " + error);
    },
  });
});
