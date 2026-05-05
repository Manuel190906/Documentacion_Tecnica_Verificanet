# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/jammy64"
  config.vm.boot_timeout = 600
  config.ssh.connect_timeout = 30
  config.ssh.insert_key = false

  # ============================================================
  # 1. ROUTER
  # ============================================================
  config.vm.define "router" do |router|
    router.vm.hostname = "router"
    router.vm.network "private_network", ip: "192.168.50.2", virtualbox__intnet: "dmz"
    router.vm.network "private_network", ip: "192.168.60.2", virtualbox__intnet: "interna"

    router.vm.provider "virtualbox" do |vb|
      vb.name = "verificanet-router"
      vb.memory = 512
      vb.customize ["modifyvm", :id, "--nictype2", "82540EM"]
      vb.customize ["modifyvm", :id, "--nictype3", "82540EM"]
    end
    router.vm.provision "shell", path: "provision/router.sh"
  end

  # ============================================================
  # 2. FIREWALL
  # ============================================================
  config.vm.define "firewall" do |firewall|
    firewall.vm.hostname = "firewall"
    firewall.vm.network "private_network", ip: "192.168.50.20", virtualbox__intnet: "dmz"
    firewall.vm.network "private_network", ip: "192.168.60.20", virtualbox__intnet: "interna"

    firewall.vm.provider "virtualbox" do |vb|
      vb.name = "verificanet-firewall"
      vb.memory = 512
      vb.customize ["modifyvm", :id, "--nictype2", "82540EM"]
      vb.customize ["modifyvm", :id, "--nictype3", "82540EM"]
    end
    firewall.vm.provision "shell", path: "provision/firewall.sh"
  end

  # ============================================================
  # 3. WEB (BALANCEADOR NGINX)
  # ============================================================
  config.vm.define "web" do |web|
    web.vm.hostname = "web"
    web.vm.network "private_network", ip: "192.168.50.30", virtualbox__intnet: "dmz"
    web.vm.network "forwarded_port", guest: 80, host: 8080, auto_correct: true

    web.vm.provider "virtualbox" do |vb|
      vb.name = "verificanet-web"
      vb.memory = 512
      vb.customize ["modifyvm", :id, "--nictype2", "82540EM"]
    end
    web.vm.provision "shell", path: "provision/web-frontend.sh"
    web.vm.provision "shell", path: "provision/Rutas_dmz.sh"
  end

  # ============================================================
  # 4. BACKENDS
  # ============================================================
  [
    {"name" => "backend1", "ip" => "192.168.50.41"},
    {"name" => "backend2", "ip" => "192.168.50.42"}
  ].each do |info|
    config.vm.define info["name"] do |be|
      be.vm.hostname = info["name"]
      be.vm.network "private_network", ip: info["ip"], virtualbox__intnet: "dmz"

      be.vm.provider "virtualbox" do |vb|
        vb.name = "verificanet-#{info["name"]}"
        vb.memory = 512
        vb.customize ["modifyvm", :id, "--nictype2", "82540EM"]
      end

      be.vm.synced_folder "./html-backup", "/vagrant/html-backup"
      be.vm.provision "shell", path: "provision/backend.sh"
      be.vm.provision "shell", path: "provision/Rutas_dmz.sh"
    end
  end

  # ============================================================
  # 5. BASE DE DATOS
  # ============================================================
  config.vm.define "database" do |db|
    db.vm.hostname = "database"
    db.vm.network "private_network", ip: "192.168.60.50", virtualbox__intnet: "interna"

    db.vm.provider "virtualbox" do |vb|
      vb.name = "verificanet-database"
      vb.memory = 1024
      vb.customize ["modifyvm", :id, "--nictype2", "82540EM"]
    end
    db.vm.provision "shell", path: "provision/database.sh"
    db.vm.provision "shell", path: "provision/Rutas_interna.sh"
  end
end