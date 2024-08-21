# MicroMesh

MicroMesh is a small and lightweight PHP framework that I built as a learning project.

### What can it do:
- Basic routing
  - Only GET and POST
- Blade template engine
- Built-in DB connection 
  - Only mysql implemented for now

### How to
Follow the `WelcomeController.php` example from `app/Controllers/` 

### Running

#### Development
First, you must install composer and npm, and run  
- ```composer create-project razvancode/micromesh=dev-main```
- ```composer install```
- Then start a PHP server:  
  ```php -S 127.0.0.1:80```

#### After you're done developing
- make `/public` folder the root

---
Made using [BladeOne](https://github.com/EFTEC/BladeOne)
