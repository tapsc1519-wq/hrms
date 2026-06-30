using System;
using System.ComponentModel;
using System.Collections.Generic;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Management;
using System.Reflection;
using System.Security.AccessControl;
using System.Security.Cryptography;
using System.Security.Principal;
using System.Text;
using System.Web.Script.Serialization;
using System.Windows.Forms;
using Microsoft.Win32;

[assembly: AssemblyTitle("OpsBridge Device Agent Setup")]
[assembly: AssemblyDescription("Installs the OpsBridge Windows Device Agent")]
[assembly: AssemblyCompany("OpsBridge")]
[assembly: AssemblyProduct("OpsBridge Device Agent")]
[assembly: AssemblyVersion("0.2.0.0")]
[assembly: AssemblyFileVersion("0.2.0.0")]

namespace OpsBridge.Agent.Setup
{
    internal sealed class InstallOptions
    {
        public string Endpoint = "";
        public string Token = "";
        public string AssetTag = "";
        public string EmployeeCode = "";
        public string EmployeeEmail = "";
        public int IntervalMinutes = 60;
    }

    internal static class AgentInstaller
    {
        private const string TaskName = "OpsBridge Device Agent";
        private const string CommandTaskName = "OpsBridge Endpoint Commands";
        private const string Version = "0.2.0";

        public static string Install(InstallOptions options)
        {
            return Install(options, null);
        }

        public static string Install(InstallOptions options, Action<string, int> progress)
        {
            Report(progress, "Validating setup details...", 8);
            Validate(options);
            Report(progress, "Creating secure agent folders...", 18);
            string root = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData), "OpsBridge", "Agent");
            Directory.CreateDirectory(root);
            Directory.CreateDirectory(Path.Combine(root, "logs"));
            Directory.CreateDirectory(Path.Combine(root, "queue"));

            Report(progress, "Copying agent files...", 30);
            WriteResource("OpsBridge.Agent.ps1", Path.Combine(root, "OpsBridge.Agent.ps1"));
            WriteResource("uninstall.ps1", Path.Combine(root, "uninstall.ps1"));
            Report(progress, "Encrypting enrollment credential...", 44);
            WriteConfig(root, options);
            Report(progress, "Protecting local agent folder...", 58);
            RestrictDirectory(root);
            Report(progress, "Registering background tasks...", 72);
            RegisterScheduledTask(root, options.IntervalMinutes);
            Report(progress, "Adding Windows uninstall entry...", 88);
            RegisterUninstallEntry(root);
            Report(progress, "Starting first check-in and command poll...", 100);

            return "Installation completed. Inventory and endpoint command services have been started.";
        }

        private static void Report(Action<string, int> progress, string message, int percent)
        {
            if (progress != null) progress(message, percent);
        }

        private static void Validate(InstallOptions options)
        {
            Uri endpoint;
            if (!Uri.TryCreate(options.Endpoint, UriKind.Absolute, out endpoint) || endpoint.Scheme != Uri.UriSchemeHttps)
                throw new InvalidOperationException("Inventory API endpoint must be a valid HTTPS URL.");
            if (String.IsNullOrWhiteSpace(options.Token) || !options.Token.StartsWith("ops_agent_", StringComparison.Ordinal))
                throw new InvalidOperationException("Enter a valid OpsBridge enrollment token.");
            if (options.IntervalMinutes < 15 || options.IntervalMinutes > 1440)
                throw new InvalidOperationException("Inventory interval must be between 15 and 1440 minutes.");
        }

        private static void WriteResource(string resourceName, string destination)
        {
            using (Stream input = Assembly.GetExecutingAssembly().GetManifestResourceStream(resourceName))
            {
                if (input == null) throw new InvalidOperationException("Installer resource is missing: " + resourceName);
                using (FileStream output = File.Create(destination)) input.CopyTo(output);
            }
        }

        private static void WriteConfig(string root, InstallOptions options)
        {
            byte[] plainToken = Encoding.UTF8.GetBytes(options.Token);
            byte[] encryptedToken = ProtectedData.Protect(plainToken, null, DataProtectionScope.LocalMachine);
            string deviceUuid = CreateDeviceUuid();
            Dictionary<string, object> config = new Dictionary<string, object>();
            config["endpoint"] = options.Endpoint.TrimEnd('/');
            config["token_ciphertext"] = Convert.ToBase64String(encryptedToken);
            config["device_uuid"] = deviceUuid;
            config["asset_tag"] = options.AssetTag.Trim();
            config["employee_code"] = options.EmployeeCode.Trim();
            config["employee_email"] = options.EmployeeEmail.Trim();
            config["sync_interval_minutes"] = options.IntervalMinutes;
            string json = new JavaScriptSerializer().Serialize(config);
            File.WriteAllText(Path.Combine(root, "config.json"), json, new UTF8Encoding(false));
        }

        private static string CreateDeviceUuid()
        {
            string machineGuid = "";
            using (RegistryKey key = Registry.LocalMachine.OpenSubKey(@"SOFTWARE\Microsoft\Cryptography"))
                if (key != null) machineGuid = Convert.ToString(key.GetValue("MachineGuid", ""));
            string serial = "";
            using (ManagementObjectSearcher searcher = new ManagementObjectSearcher("SELECT SerialNumber FROM Win32_BIOS"))
                foreach (ManagementObject item in searcher.Get()) { serial = Convert.ToString(item["SerialNumber"]); break; }
            using (SHA256 sha = SHA256.Create())
            {
                byte[] hash = sha.ComputeHash(Encoding.UTF8.GetBytes(machineGuid + "|" + serial));
                StringBuilder value = new StringBuilder(hash.Length * 2);
                foreach (byte item in hash) value.Append(item.ToString("x2"));
                return value.ToString();
            }
        }

        private static void RestrictDirectory(string root)
        {
            DirectorySecurity security = new DirectorySecurity();
            security.SetAccessRuleProtection(true, false);
            InheritanceFlags inheritance = InheritanceFlags.ContainerInherit | InheritanceFlags.ObjectInherit;
            security.AddAccessRule(new FileSystemAccessRule(new SecurityIdentifier(WellKnownSidType.LocalSystemSid, null), FileSystemRights.FullControl, inheritance, PropagationFlags.None, AccessControlType.Allow));
            security.AddAccessRule(new FileSystemAccessRule(new SecurityIdentifier(WellKnownSidType.BuiltinAdministratorsSid, null), FileSystemRights.FullControl, inheritance, PropagationFlags.None, AccessControlType.Allow));
            Directory.SetAccessControl(root, security);
        }

        private static void RegisterScheduledTask(string root, int intervalMinutes)
        {
            string script = Path.Combine(root, "OpsBridge.Agent.ps1");
            string escapedScript = script.Replace("'", "''");
            string escapedTaskName = TaskName.Replace("'", "''");
            string escapedCommandTaskName = CommandTaskName.Replace("'", "''");
            string command =
                "$ErrorActionPreference='Stop';" +
                "$inventoryAction=New-ScheduledTaskAction -Execute 'powershell.exe' -Argument '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File \"" + escapedScript + "\"';" +
                "$commandAction=New-ScheduledTaskAction -Execute 'powershell.exe' -Argument '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File \"" + escapedScript + "\" -CommandsOnly';" +
                "$inventoryTrigger=New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes " + intervalMinutes + ");" +
                "$commandTrigger=New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(2) -RepetitionInterval (New-TimeSpan -Minutes 5);" +
                "$settings=New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 10);" +
                "Register-ScheduledTask -TaskName '" + escapedTaskName + "' -Action $inventoryAction -Trigger $inventoryTrigger -Settings $settings -User 'SYSTEM' -RunLevel Highest -Force | Out-Null;" +
                "Register-ScheduledTask -TaskName '" + escapedCommandTaskName + "' -Action $commandAction -Trigger $commandTrigger -Settings $settings -User 'SYSTEM' -RunLevel Highest -Force | Out-Null;" +
                "Start-ScheduledTask -TaskName '" + escapedTaskName + "';Start-ScheduledTask -TaskName '" + escapedCommandTaskName + "'";
            string encodedCommand = Convert.ToBase64String(Encoding.Unicode.GetBytes(command));
            RunProcess("powershell.exe", "-NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand " + encodedCommand);
        }

        private static void RegisterUninstallEntry(string root)
        {
            const string registryPath = @"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\OpsBridgeDeviceAgent";
            using (RegistryKey key = Registry.LocalMachine.CreateSubKey(registryPath))
            {
                if (key == null) throw new InvalidOperationException("Unable to register the Windows uninstall entry.");
                string uninstallScript = Path.Combine(root, "uninstall.ps1");
                key.SetValue("DisplayName", "OpsBridge Device Agent");
                key.SetValue("DisplayVersion", Version);
                key.SetValue("Publisher", "OpsBridge");
                key.SetValue("InstallLocation", root);
                key.SetValue("UninstallString", "powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"" + uninstallScript + "\"");
                key.SetValue("NoModify", 1, RegistryValueKind.DWord);
                key.SetValue("NoRepair", 1, RegistryValueKind.DWord);
            }
        }

        private static void RunProcess(string fileName, string arguments)
        {
            ProcessStartInfo start = new ProcessStartInfo(fileName, arguments);
            start.UseShellExecute = false;
            start.CreateNoWindow = true;
            start.RedirectStandardOutput = true;
            start.RedirectStandardError = true;
            using (Process process = Process.Start(start))
            {
                string output = process.StandardOutput.ReadToEnd();
                string error = process.StandardError.ReadToEnd();
                process.WaitForExit();
                if (process.ExitCode != 0) throw new InvalidOperationException((error + Environment.NewLine + output).Trim());
            }
        }
    }

    internal sealed class SetupForm : Form
    {
        private TextBox endpoint;
        private TextBox token;
        private TextBox assetTag;
        private TextBox employeeCode;
        private TextBox employeeEmail;
        private NumericUpDown interval;
        private Button installButton;
        private Label status;
        private ProgressBar progressBar;
        private ListBox progressLog;

        public SetupForm(Dictionary<string, string> arguments)
        {
            Text = "OpsBridge Device Agent Setup";
            ClientSize = new Size(720, 720);
            MinimumSize = new Size(736, 759);
            MaximizeBox = false;
            StartPosition = FormStartPosition.CenterScreen;
            BackColor = Color.FromArgb(247, 249, 252);
            Font = new Font("Segoe UI", 9F);
            AutoScaleMode = AutoScaleMode.Dpi;

            Panel header = new Panel();
            header.Dock = DockStyle.Top;
            header.Height = 116;
            header.BackColor = Color.FromArgb(15, 23, 42);
            Controls.Add(header);

            Panel mark = new Panel();
            mark.Location = new Point(34, 28);
            mark.Size = new Size(54, 54);
            mark.BackColor = Color.FromArgb(37, 99, 235);
            header.Controls.Add(mark);
            Label markText = new Label();
            markText.Text = "O";
            markText.ForeColor = Color.White;
            markText.Font = new Font("Segoe UI", 24F, FontStyle.Bold);
            markText.TextAlign = ContentAlignment.MiddleCenter;
            markText.Dock = DockStyle.Fill;
            mark.Controls.Add(markText);

            Label title = new Label();
            title.Text = "OpsBridge Device Agent";
            title.ForeColor = Color.White;
            title.Font = new Font("Segoe UI", 18F, FontStyle.Bold);
            title.AutoSize = true;
            title.Location = new Point(106, 27);
            header.Controls.Add(title);
            Label subtitle = new Label();
            subtitle.Text = "Secure inventory setup for this Windows device";
            subtitle.ForeColor = Color.FromArgb(191, 219, 254);
            subtitle.AutoSize = true;
            subtitle.Location = new Point(109, 67);
            header.Controls.Add(subtitle);

            endpoint = AddField("Inventory API endpoint", 142, 32, 656, false);
            token = AddField("Enrollment token", 210, 32, 656, true);
            assetTag = AddField("Asset tag (optional)", 278, 32, 316, false);
            employeeCode = AddField("Employee code (optional)", 278, 372, 316, false);
            employeeEmail = AddField("Employee email (optional)", 346, 32, 456, false);

            Label intervalLabel = FieldLabel("Interval (minutes)", 346, 480);
            Controls.Add(intervalLabel);
            interval = new NumericUpDown();
            interval.Location = new Point(480, 369);
            interval.Size = new Size(208, 27);
            interval.Minimum = 15;
            interval.Maximum = 1440;
            interval.Value = 60;
            Controls.Add(interval);

            CheckBox showToken = new CheckBox();
            showToken.Text = "Show enrollment token";
            showToken.AutoSize = true;
            showToken.Location = new Point(32, 413);
            showToken.CheckedChanged += delegate { token.UseSystemPasswordChar = !showToken.Checked; };
            Controls.Add(showToken);

            Panel note = new Panel();
            note.Location = new Point(32, 446);
            note.Size = new Size(656, 58);
            note.BackColor = Color.FromArgb(239, 246, 255);
            Controls.Add(note);
            Label noteText = new Label();
            noteText.Text = "The enrollment token is encrypted for this computer. After first check-in, it is replaced by a unique device credential.";
            noteText.ForeColor = Color.FromArgb(30, 64, 175);
            noteText.Location = new Point(14, 11);
            noteText.Size = new Size(628, 40);
            note.Controls.Add(noteText);

            progressBar = new ProgressBar();
            progressBar.Location = new Point(32, 526);
            progressBar.Size = new Size(656, 18);
            progressBar.Minimum = 0;
            progressBar.Maximum = 100;
            Controls.Add(progressBar);

            progressLog = new ListBox();
            progressLog.Location = new Point(32, 556);
            progressLog.Size = new Size(656, 82);
            progressLog.BorderStyle = BorderStyle.FixedSingle;
            progressLog.BackColor = Color.White;
            progressLog.ForeColor = Color.FromArgb(51, 65, 85);
            Controls.Add(progressLog);

            installButton = new Button();
            installButton.Text = "Install Device Agent";
            installButton.Location = new Point(478, 658);
            installButton.Size = new Size(210, 42);
            installButton.BackColor = Color.FromArgb(37, 99, 235);
            installButton.ForeColor = Color.White;
            installButton.FlatStyle = FlatStyle.Flat;
            installButton.FlatAppearance.BorderSize = 0;
            installButton.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            installButton.Click += InstallClicked;
            Controls.Add(installButton);

            status = new Label();
            status.Location = new Point(32, 658);
            status.Size = new Size(430, 54);
            status.ForeColor = Color.FromArgb(71, 85, 105);
            status.Text = "Ready to install. Administrator approval is required.";
            Controls.Add(status);

            endpoint.Text = Value(arguments, "endpoint");
            token.Text = Value(arguments, "token");
            assetTag.Text = Value(arguments, "asset-tag");
            employeeCode.Text = Value(arguments, "employee-code");
            employeeEmail.Text = Value(arguments, "employee-email");
            int parsedInterval;
            if (Int32.TryParse(Value(arguments, "interval"), out parsedInterval) && parsedInterval >= 15 && parsedInterval <= 1440) interval.Value = parsedInterval;
        }

        private TextBox AddField(string label, int top, int left, int width, bool password)
        {
            Controls.Add(FieldLabel(label, top, left));
            TextBox box = new TextBox();
            box.Location = new Point(left, top + 23);
            box.Size = new Size(width, 27);
            box.UseSystemPasswordChar = password;
            Controls.Add(box);
            return box;
        }

        private Label FieldLabel(string text, int top, int left)
        {
            Label label = new Label();
            label.Text = text;
            label.AutoSize = true;
            label.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label.ForeColor = Color.FromArgb(51, 65, 85);
            label.Location = new Point(left, top);
            return label;
        }

        private void InstallClicked(object sender, EventArgs e)
        {
            InstallOptions options = ReadOptions();
            SetInstalling(true);
            progressBar.Value = 0;
            progressLog.Items.Clear();
            AddProgress("Starting installation...", 0);

            BackgroundWorker worker = new BackgroundWorker();
            worker.WorkerReportsProgress = true;
            worker.DoWork += delegate(object workerSender, DoWorkEventArgs workerArgs)
            {
                workerArgs.Result = AgentInstaller.Install(options, delegate(string message, int percent)
                {
                    ((BackgroundWorker)workerSender).ReportProgress(percent, message);
                });
            };
            worker.ProgressChanged += delegate(object workerSender, ProgressChangedEventArgs progressArgs)
            {
                AddProgress(Convert.ToString(progressArgs.UserState), progressArgs.ProgressPercentage);
            };
            worker.RunWorkerCompleted += delegate(object workerSender, RunWorkerCompletedEventArgs completedArgs)
            {
                if (completedArgs.Error == null)
                {
                    string result = Convert.ToString(completedArgs.Result);
                    progressBar.Value = 100;
                    status.ForeColor = Color.FromArgb(5, 150, 105);
                    status.Text = result;
                    installButton.Text = "Installed";
                    MessageBox.Show(result, "OpsBridge Setup", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    Close();
                    return;
                }

                SetInstalling(false);
                status.ForeColor = Color.FromArgb(220, 38, 38);
                status.Text = completedArgs.Error.Message;
                MessageBox.Show(completedArgs.Error.Message, "Installation could not be completed", MessageBoxButtons.OK, MessageBoxIcon.Error);
            };

            try
            {
                worker.RunWorkerAsync();
            }
            catch (Exception exception)
            {
                SetInstalling(false);
                status.ForeColor = Color.FromArgb(220, 38, 38);
                status.Text = exception.Message;
                MessageBox.Show(exception.Message, "Installation could not be started", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void SetInstalling(bool installing)
        {
            endpoint.Enabled = !installing;
            token.Enabled = !installing;
            assetTag.Enabled = !installing;
            employeeCode.Enabled = !installing;
            employeeEmail.Enabled = !installing;
            interval.Enabled = !installing;
            installButton.Enabled = !installing;
            status.ForeColor = installing ? Color.FromArgb(30, 64, 175) : Color.FromArgb(71, 85, 105);
            status.Text = installing ? "Installing agent. Please keep this window open." : "Ready to install. Administrator approval is required.";
        }

        private void AddProgress(string message, int percent)
        {
            if (percent < progressBar.Minimum) percent = progressBar.Minimum;
            if (percent > progressBar.Maximum) percent = progressBar.Maximum;
            progressBar.Value = percent;
            if (!String.IsNullOrWhiteSpace(message))
            {
                progressLog.Items.Add(DateTime.Now.ToString("HH:mm:ss") + "  " + message);
                progressLog.TopIndex = progressLog.Items.Count - 1;
                status.Text = message;
            }
        }

        private InstallOptions ReadOptions()
        {
            InstallOptions options = new InstallOptions();
            options.Endpoint = endpoint.Text.Trim();
            options.Token = token.Text.Trim();
            options.AssetTag = assetTag.Text.Trim();
            options.EmployeeCode = employeeCode.Text.Trim();
            options.EmployeeEmail = employeeEmail.Text.Trim();
            options.IntervalMinutes = Decimal.ToInt32(interval.Value);
            return options;
        }

        private static string Value(Dictionary<string, string> values, string key)
        {
            string result;
            return values.TryGetValue(key, out result) ? result : "";
        }
    }

    internal static class Program
    {
        [STAThread]
        private static int Main(string[] args)
        {
            Dictionary<string, string> values = ParseArguments(args);
            if (values.ContainsKey("silent"))
            {
                try
                {
                    InstallOptions options = new InstallOptions();
                    options.Endpoint = Value(values, "endpoint");
                    options.Token = Value(values, "token");
                    options.AssetTag = Value(values, "asset-tag");
                    options.EmployeeCode = Value(values, "employee-code");
                    options.EmployeeEmail = Value(values, "employee-email");
                    int interval;
                    if (Int32.TryParse(Value(values, "interval"), out interval)) options.IntervalMinutes = interval;
                    AgentInstaller.Install(options);
                    return 0;
                }
                catch (Exception exception)
                {
                    try { File.AppendAllText(Path.Combine(Path.GetTempPath(), "OpsBridge-Agent-Setup.log"), DateTime.Now.ToString("s") + " " + exception + Environment.NewLine); }
                    catch { }
                    return 1;
                }
            }

            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new SetupForm(values));
            return 0;
        }

        private static Dictionary<string, string> ParseArguments(string[] args)
        {
            Dictionary<string, string> values = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);
            foreach (string argument in args)
            {
                string item = argument.TrimStart('-', '/');
                int separator = item.IndexOf('=');
                if (separator < 0) values[item] = "true";
                else values[item.Substring(0, separator)] = item.Substring(separator + 1).Trim('"');
            }
            return values;
        }

        private static string Value(Dictionary<string, string> values, string key)
        {
            string result;
            return values.TryGetValue(key, out result) ? result : "";
        }
    }
}
