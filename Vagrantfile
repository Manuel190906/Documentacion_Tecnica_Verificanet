# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  # Box por defecto para Linux
  config.vm.box = "ubuntu/jammy64"

  # ============================================================
  # FIREWALL / ROUTER (NAT + DMZ + RED INTERNA)
  # ============================================================
  config.vm.define "firewall" do |firewall|
    firewall.vm.hostname = "firewall"

    # Adaptador 1: NAT (lo añade Vagrant por defecto) → enp0s3 (10.0.2.x)
    # Salida a Internet + SSH de Vagrant

    # Adaptador 2: DMZ 192.168.50.1
    firewall.vm.network "private_network",
      ip: "192.168.50.1",
      virtualbox__intnet: "dmz"

    # Adaptador 3: RED INTERNA 192.168.60.1
    firewall.vm.network "private_network",
      ip: "192.168.60.1",
      virtualbox__intnet: "interna"

    firewall.vm.provider "virtualbox" do |vb|
      vb.name   = "verificanet-firewall"
      vb.memory = 512
      vb.cpus   = 1
    end

    firewall.vm.provision "shell", path: "provision/firewall.sh"
  end

  # ============================================================
  # BALANCEADOR WEB (NGINX) EN DMZ
  # ============================================================
  config.vm.define "web" do |web|
    web.vm.hostname = "web"

    # Solo en DMZ
    web.vm.network "private_network",
      ip: "192.168.50.30",
      virtualbox__intnet: "dmz"

    # Acceso HTTP desde el host
    web.vm.network "forwarded_port",
      guest: 80,
      host: 8080,
      auto_correct: true

    web.vm.provider "virtualbox" do |vb|
      vb.name   = "verificanet-web"
      vb.memory = 512
      vb.cpus   = 1
    end

    web.vm.provision "shell", path: "provision/web-frontend.sh"
    web.vm.provision "shell", path: "provision/Rutas_dmz.sh"
  end

  # ============================================================
  # BACKEND 1 (DMZ + RED INTERNA)
  # ============================================================
  config.vm.define "backend1" do |backend1|
    backend1.vm.hostname = "backend1"

    # DMZ
    backend1.vm.network "private_network",
      ip: "192.168.50.41",
      virtualbox__intnet: "dmz"

    # RED INTERNA
    backend1.vm.network "private_network",
      ip: "192.168.60.41",
      virtualbox__intnet: "interna"

    backend1.vm.provider "virtualbox" do |vb|
      vb.name   = "verificanet-backend1"
      vb.memory = 512
      vb.cpus   = 1
    end

    backend1.vm.provision "shell", path: "provision/backend.sh"
    backend1.vm.provision "shell", path: "provision/Rutas_dmz.sh"
  end

  # ============================================================
  # BACKEND 2 (DMZ + RED INTERNA)
  # ============================================================
  config.vm.define "backend2" do |backend2|
    backend2.vm.hostname = "backend2"

    # DMZ
    backend2.vm.network "private_network",
      ip: "192.168.50.42",
      virtualbox__intnet: "dmz"

    # RED INTERNA
    backend2.vm.network "private_network",
      ip: "192.168.60.42",
      virtualbox__intnet: "interna"

    backend2.vm.provider "virtualbox" do |vb|
      vb.name   = "verificanet-backend2"
      vb.memory = 512
      vb.cpus   = 1
    end

    backend2.vm.provision "shell", path: "provision/backend.sh"
    backend2.vm.provision "shell", path: "provision/Rutas_dmz.sh"
  end

  # ============================================================
  # BASE DE DATOS (SOLO RED INTERNA)
  # ============================================================
  config.vm.define "database" do |db|
    db.vm.hostname = "database"

    # Solo en RED INTERNA
    db.vm.network "private_network",
      ip: "192.168.60.50",
      virtualbox__intnet: "interna"

    db.vm.provider "virtualbox" do |vb|
      vb.name   = "verificanet-database"
      vb.memory = 1024
      vb.cpus   = 1
    end

    db.vm.provision "shell", path: "provision/database.sh"
    db.vm.provision "shell", path: "provision/Rutas_interna.sh"
  end
end