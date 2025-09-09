import com.ibm.eNetwork.ECL.*;

public class CursorPosition {
    public static void main(String[] args) {
        ECLSession session = new ECLSession();
        
        try {
            session.SetConnectionByName("A");
            session.StartCommunication();
            
            ECLScreenDesc screen = new ECLScreenDesc();
            session.GetScreen(screen);
            
            int row = session.GetCursorPos().y + 1;
            int col = session.GetCursorPos().x + 1;
            
            System.out.println("Line: " + row + ", Column: " + col);
            
        } catch (Exception e) {
            System.err.println("Error: " + e.getMessage());
        } finally {
            try {
                session.StopCommunication();
            } catch (Exception e) {}
        }
    }
}