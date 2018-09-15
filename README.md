# Backend summer school 2018 - итоговая работа

Многопользовательское приложение для управления складскими запасами. Сервис позволяет каждому пользователю завести учетную запись и в рамках нее вести управление складами и запасами товаров на складах. Реализовано как backend сервис работающий по протоколу HTTP и использующий стандарт RESTful API. Cтек - PHP, MySQL. Для аутентификации используется HTTP Basic Auth, где параметром будет пара логин-пароль. Все POST-, PUT- и DELETE-запросы должны быть в формате JSON. Все ответы также возвращаются в формате JSON.


## Описание методов API

### POST /api/v1/users
создает нового пользователя

#### Параметры
- login - имя (идентификатор) учётной записи
- password - пароль
- name - имя
- surname - фамилия
- organization - организация 
- email - адрес электронной почты
- phoneNumber - номер телефона

#### Ограничения: 
- двух человек с одинаковым именем в одной организации быть не может
- один email не может использоваться более чем одним пользователем

#### Пример запроса 
```json
{
	"login": "Toma",
	"name": "Tamara",
	"surname": "Vedenina",
	"password": "qwerty",
	"organization": "PSU",
	"email": "toma@gmail.com",
	"phoneNumber": "88005553535"
}
```
после успешного выполнения возвращает созданный объект
```json
{
    "id": "7",
    "login": "Toma",
    "name": "Tamara",
    "surname": "Vedenina",
    "organization": "PSU",
    "email": "toma5@gmail.com",
    "phoneNumber": "88005553535"
}
```

### GET /api/v1/me
возвращает информацию о текущем пользователе

#### Пример запроса: 
```json
{
    "id": 5,
    "login": "test",
    "name": "Prtya",
    "surname": "Ivanov",
    "organization": "PSU",
    "email": "toma@gmail.com",
    "phoneNumber": "88005553535"
}
```

PUT /api/v1/me - обновить данные о текущем пользователе

DELETE /api/v1/me - удалить учетную запись и все связанные с ней данные


GET /api/v1/products
Возвращает список всех продуктов пользователя

POST /api/v1/products - добавить продукт

PUT /api/v1/products/{sku} - изменить продукт

GET /api/v1/products/{sku} - информация об одном продукте

DELETE /api/v1/products/{sku} - удалить продукт


GET /api/v1/warehouses - информация обо всех складах

POST /api/v1/warehouses - добавить склад

PUT /api/v1/warehouses/{id} - изменить склад

GET /api/v1/warehouses/{id} - информация об одном складе

DELETE /api/v1/warehouses/{id} - удалить склад


PUT /api/v1/warehouses/6/receipt - получить продукты на склад

PUT /api/v1/warehouses/6/dispatch - отправить продукты со склада

PUT /api/v1/warehouses/6/movement - переместить на другой склад

GET /api/v1/warehouses/{id}/residues - получить текущее состояние по остаткам на конкретном складе в количестве и общей стоимости всех товаров

GET /api/v1/warehouses/{id}/residues/{date} - получить на конкретную дату состояние по остаткам на конкретном складе по количеству и общей стоимости товаров (формат даты 'Y-m-d')


GET /api/v1/products/{sku}/residues - получить текущее состояние остатков по товару по всем складам  в количестве и общей стоимости

GET /api/v1/products/{sku}/residues/{date} - получить на конкретную дату состояние остатков по товару по всем складам и общей стоимости товаров (формат даты 'Y-m-d')

GET /api/v1/warehouses/{id}/movements - получить все движения товаров по конкретному складу

GET /api/v1/products/{sku}/movements - получить все движения конкретного товара по складам с учетом сумм и остатков

