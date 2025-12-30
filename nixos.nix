{
  config,
  pkgs,
  lib,
  ...
}:

let
  cfg = config.services.phpelo;
  phpelo-scripts = pkgs.runCommand "phpelo-scripts" { } ''
    mkdir -p $out
    cp ${./entrypoint.php} $out/entrypoint.php
    cp ${./markdown.php} $out/markdown.php
  '';
in

{
  options = {
    services.phpelo = {
      enable = (lib.mkEnableOption "phpelo") // {
        default = true;
      };
      php = lib.mkPackageOption pkgs "php" { };
      scriptDir = lib.mkOption {
        description = "Where are the scripts";
        default = "/etc/phpelo";
        type = lib.types.str;
      };
      socket = lib.mkOption {
        description = "Where to listen socket for phpelo";
        default = "/run/phpelo.sock";
      };
    };
  };
  config = lib.mkIf cfg.enable {

    systemd.sockets.phpelo = {
      restartTriggers = [ cfg.socket ];
      socketConfig = {
        ListenStream = cfg.socket;
        Accept = true;
      };
      partOf = [ "phpelo.service" ];
      wantedBy = [
        "sockets.target"
        "multi-user.target"
      ];
    };

    systemd.slices.phpelo.sliceConfig = {
      MemoryMax = "64M";
      MemoryHigh = "16M";
      CPUQuota = "10%";
      ManagedOOMSwap = "kill";
      ManagedOOMPressure = "kill";
    };

    systemd.services."phpelo@" = {
      stopIfChanged = true;
      after = [ "network.target" ];
      serviceConfig = {
        Slice = "phpelo.slice";
        StandardInput = "socket";
        StandardOutput = "socket";
        StandardError = "journal";

        DevicePolicy = "closed";
        MemoryDenyWriteExecute = true;
        NoNewPrivileges = true;
        PrivateDevices = true;
        PrivateTmp = true;
        ProtectControlGroups = true;
        # ProtectHome = true;
        ProtectKernelModules = true;
        ProtectKernelTunables = true;
        ProtectKernelLogs = true;
        ProtectSystem = "strict";
      };

      script = ''
        export SCRIPT_DIR="${phpelo-scripts}"
        exec ${lib.getExe cfg.php}  -d display_errors="stderr" -d disable_functions="header" ${phpelo-scripts}/entrypoint.php
      '';
    };
  };
}
