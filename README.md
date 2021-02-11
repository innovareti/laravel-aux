 # laravel-aux
  Pacote que tem como intuito auxiliar na padronização e criação de um CRUD de uma API (**Laravel**) que segue o padrão 
  (**View** -> **Controller** -> **Service** -> **Repository** -> **Model**)
  
  - **View**: Responsável por mostrar as informações aos usuários e captar os eventos/ações.
  - **Controller**: Responsável apenas por receber uma solicitação/ação do usuário e requisitar para uma Service.
  - **Service**: Responsável por conter as regras de negócio no geral, porém nunca realizando as queries e sim, passando essa     responsabilidade para as repositories.
  - **Repository**: Responsável pela realização das queries através da utilização da model. Pode ser definida como uma camada     de segurança.
  - **Model**: Responsável por buscar/atualizar/remover e/ou modificar os dados no banco de dados através do consumo do ORM do   Laravel (**Eloquent**).


  Examples of usage:

    List all elements:
        Paginated:
            http://localhost:8000/api/v1/users
        Whithout pagination:
            http://localhost:8000/api/v1/users?paginated=false

    Order:
        Asc:
            http://localhost:8000/api/v1/users?orderByAsc[]=name
        Desc:
            http://localhost:8000/api/v1/users?orderByDesc[]=id
        
    Get element by id
        http://localhost:8000/api/v1/users?id=1

    Bring with relationships
        http://localhost:8000/api/v1/users?with[]=company

    Filter by any field, including children fields
        Filtering by parent fields -> will bring all which name matches input 'name' (Ma), like Mateus, Maicon, Marcos
            http://localhost:8000/api/v1/users?name=Ma
            http://localhost:8000/api/v1/users?name=Ma,Lu
        Filtering by children -> will bring all which relation (companies) matches input 'name' (hospital) from companies table   
            http://localhost:8000/api/v1/users?with[]=company&name=Mat&company[name]=hospital

    Group by any field -> will bring data grouped by the value of field (company_id)
        http://localhost:8000/api/v1/users?with[]=company&groupBy[]=company_id