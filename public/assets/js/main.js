$(document).ready(() => {
    $("#sendfiles").change((e) => {
        var $input = $("#sendfiles");
        e.preventDefault();
        var formData = new FormData();
		$.each($input[0].files,function(key, input){
			formData.append('images[]', input);
		});
        //Query 
        $.ajax({
            url: "/sendfile/"+$input.data("id"),
            type: 'POST',
            contentType: false,
            cache: false,
            processData: false,
            dataType: 'json',
            data: formData,
            success: function(data) {
                var html = "";
                for (let i = 0; i < data.length; i++) {
                    html += `<div class="image-item">
                    <a class="image-del" href="/deleteimage/${$input.data('id')}/${i+1}">x</a>
                    <img src="/assets/img/${data[i]}" alt="Изображение ${i}"/>
                </div>`;
                }
                $(".image-list").html(html);
            }
        });
    })
    $("#tasks .task").each(function( index ) {
        var dateText = $(this).find(".datetime").html();
        var date = new Date(Date.parse(dateText.replace(/-/g, '/')));
        var deltaTime = new Date();
        deltaTime -= date;
        if ((deltaTime/1000/60) > 60){
            $(this).css("background-color", "#ff000077");
        }
        
        console.log();
    });
    if ($("#tasks")){
        const updatedata = () => {
            $.ajax({
                url: "/gettasks",
                type: 'POST',
                contentType: false,
                cache: false,
                processData: false,
                dataType: 'json',
                success: function(data) {
                    let html = "";
                    for (el of data) {
                        html += ` <tr class="task" data-id="${el.id}">
                        <td>${el.id}</td>
                        <td>${el.title}</td>
                        <td class="images-cell">${el.images.replaceAll(/,/g,',<br/>')}</td>
                        <td><p>${el.text}</p></td>
                        <td class="datetime">${el.createtime}</td>
                        <td>${el.updatetime}</td>
                        <td>`;
                        switch (el.status) {
                            case "0":
                                html += "отменена"
                                break;
                            case "1":
                                html += "новая"
                                break;
                            case "2":
                                html += "в работе"
                                break;
                            case "3":
                                html += "завершена"
                                break;
                        }
                        html +=`</td>
                        <td>
                            <a href="/update/${el.id}"><button class="button w-100 update" id="id${el.id}">Изменить</button></a><br/>
                            <a href="/delete/${el.id}"><button class="button w-100 delete" id="id${el.id}">Удалить</button></a><br/>
                        </td>
                    </tr>`;
                    }
                    $("tbody").html(html);
                }
            });
        };
        updatedata();
        setInterval(updatedata, 3000);
    }
})