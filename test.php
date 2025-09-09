import java.io.*;

public class CursorPosition {
    public static void main(String[] args) {
        try {
            // Use PCOMM automation object via command line
            ProcessBuilder pb = new ProcessBuilder("cmd", "/c", 
                "echo Set sess = CreateObject(\"PCOMM.autECLSession\") > temp.vbs && " +
                "echo sess.SetConnectionByName \"A\" >> temp.vbs && " +
                "echo WScript.Echo sess.autECLPS.CursorPosRow ^& \",\" ^& sess.autECLPS.CursorPosCol >> temp.vbs && " +
                "cscript //nologo temp.vbs && del temp.vbs");
            
            Process p = pb.start();
            BufferedReader reader = new BufferedReader(new InputStreamReader(p.getInputStream()));
            String result = reader.readLine();
            
            if (result != null && result.contains(",")) {
                String[] pos = result.split(",");
                System.out.println("Line: " + pos[0] + ", Column: " + pos[1]);
            }
        } catch (Exception e) {
            System.err.println("Error: " + e.getMessage());
        }
    }
}